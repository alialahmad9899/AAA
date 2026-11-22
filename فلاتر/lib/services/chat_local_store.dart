// Persistent account-scoped chat cache for offline reading and chat preferences.
import 'dart:convert';

import 'package:cryptography/cryptography.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:sqflite/sqflite.dart';

import '../models/chat_model.dart';

class ChatLocalStore {
  ChatLocalStore._();

  static Database? _database;
  static Future<SecretKey>? _cacheEncryptionKeyFuture;
  static const int maxMessagesPerThread = 2000;
  static const String _encryptionKeyStorageName = 'wesal_chat_cache_aes_key_v1';
  static const String _encryptedPayloadPrefix = 'enc:v1:';
  static final AesGcm _payloadCipher = AesGcm.with256bits();
  static const FlutterSecureStorage _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock_this_device),
  );

  static Future<SecretKey> _cacheEncryptionKey() {
    return _cacheEncryptionKeyFuture ??= _loadCacheEncryptionKey();
  }

  static Future<SecretKey> _loadCacheEncryptionKey() async {
    final stored = await _secureStorage.read(key: _encryptionKeyStorageName);
    if (stored != null && stored.isNotEmpty) {
      try {
        final bytes = base64Decode(stored);
        if (bytes.length == 32) return SecretKey(bytes);
      } catch (_) {
        // Replace an invalid key rather than attempting unauthenticated reads.
      }
    }

    final generated = await _payloadCipher.newSecretKey();
    final bytes = await generated.extractBytes();
    await _secureStorage.write(
      key: _encryptionKeyStorageName,
      value: base64Encode(bytes),
    );
    return SecretKey(bytes);
  }

  static Future<String> _encryptPayload(String plainText) async {
    if (plainText.startsWith(_encryptedPayloadPrefix)) return plainText;
    final key = await _cacheEncryptionKey();
    final box = await _payloadCipher.encrypt(
      utf8.encode(plainText),
      secretKey: key,
    );
    return '$_encryptedPayloadPrefix${base64Encode(box.nonce)}:${base64Encode(box.cipherText)}:${base64Encode(box.mac.bytes)}';
  }

  static Future<String> _decryptPayload(String storedText) async {
    if (!storedText.startsWith(_encryptedPayloadPrefix)) {
      // Plaintext v3 rows are converted in the v4 database migration.
      return storedText;
    }
    final parts = storedText.split(':');
    if (parts.length != 5 || parts[0] != 'enc' || parts[1] != 'v1') {
      throw const FormatException('Unsupported encrypted chat cache payload.');
    }
    final key = await _cacheEncryptionKey();
    final box = SecretBox(
      base64Decode(parts[3]),
      nonce: base64Decode(parts[2]),
      mac: Mac(base64Decode(parts[4])),
    );
    final clearBytes = await _payloadCipher.decrypt(box, secretKey: key);
    return utf8.decode(clearBytes);
  }

  static Future<void> _encryptLegacyPayloads(DatabaseExecutor db) async {
    final threadRows = await db.query(
      'chat_threads',
      columns: ['account_id', 'thread_id', 'payload'],
    );
    for (final row in threadRows) {
      final payload = row['payload'] as String?;
      if (payload == null || payload.startsWith(_encryptedPayloadPrefix)) continue;
      await db.update(
        'chat_threads',
        {'payload': await _encryptPayload(payload)},
        where: 'account_id = ? AND thread_id = ?',
        whereArgs: [row['account_id'], row['thread_id']],
      );
    }

    final messageRows = await db.query(
      'chat_messages',
      columns: ['account_id', 'thread_id', 'message_id', 'payload'],
    );
    for (final row in messageRows) {
      final payload = row['payload'] as String?;
      if (payload == null || payload.startsWith(_encryptedPayloadPrefix)) continue;
      await db.update(
        'chat_messages',
        {'payload': await _encryptPayload(payload)},
        where: 'account_id = ? AND thread_id = ? AND message_id = ?',
        whereArgs: [row['account_id'], row['thread_id'], row['message_id']],
      );
    }
  }

  static Future<Database> get _db async {
    if (_database != null) return _database!;
    final databasePath = await getDatabasesPath();
    final path = '$databasePath/wesal_chat_cache.db';
    _database = await openDatabase(
      path,
      version: 4,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE chat_threads (
            account_id INTEGER NOT NULL,
            thread_id INTEGER NOT NULL,
            payload TEXT NOT NULL,
            updated_at INTEGER NOT NULL,
            PRIMARY KEY (account_id, thread_id)
          )
        ''');
        await db.execute('''
          CREATE TABLE chat_messages (
            account_id INTEGER NOT NULL,
            thread_id INTEGER NOT NULL,
            message_id INTEGER NOT NULL,
            payload TEXT NOT NULL,
            PRIMARY KEY (account_id, thread_id, message_id)
          )
        ''');
        await db.execute('''
          CREATE INDEX chat_messages_thread_order
          ON chat_messages (account_id, thread_id, message_id)
        ''');
        await db.execute('''
          CREATE TABLE chat_thread_preferences (
            account_id INTEGER NOT NULL,
            thread_id INTEGER NOT NULL,
            muted INTEGER NOT NULL DEFAULT 0,
            scroll_offset REAL NOT NULL DEFAULT 0,
            PRIMARY KEY (account_id, thread_id)
          )
        ''');
        await db.execute('''
          CREATE TABLE chat_mute_preferences (
            account_id INTEGER NOT NULL,
            partner_user_id INTEGER NOT NULL,
            muted INTEGER NOT NULL,
            sync_pending INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL,
            PRIMARY KEY (account_id, partner_user_id)
          )
        ''');
        await db.execute('''
          CREATE TABLE chat_hidden_messages (
            account_id INTEGER NOT NULL,
            thread_id INTEGER NOT NULL,
            message_id INTEGER NOT NULL,
            hidden_at INTEGER NOT NULL,
            PRIMARY KEY (account_id, thread_id, message_id)
          )
        ''');
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        if (oldVersion < 2) {
          await db.execute('''
            CREATE TABLE chat_mute_preferences (
              account_id INTEGER NOT NULL,
              partner_user_id INTEGER NOT NULL,
              muted INTEGER NOT NULL,
              sync_pending INTEGER NOT NULL DEFAULT 0,
              updated_at INTEGER NOT NULL,
              PRIMARY KEY (account_id, partner_user_id)
            )
          ''');
          await db.execute('''
            CREATE TABLE chat_hidden_messages (
              account_id INTEGER NOT NULL,
              thread_id INTEGER NOT NULL,
              message_id INTEGER NOT NULL,
              hidden_at INTEGER NOT NULL,
              PRIMARY KEY (account_id, thread_id, message_id)
            )
          ''');
          final legacyMutes = await db.rawQuery('''
            SELECT preferences.account_id, preferences.muted, threads.payload
            FROM chat_thread_preferences AS preferences
            INNER JOIN chat_threads AS threads
              ON threads.account_id = preferences.account_id
             AND threads.thread_id = preferences.thread_id
            WHERE preferences.muted = 1
          ''');
          final now = DateTime.now().millisecondsSinceEpoch;
          for (final row in legacyMutes) {
            try {
              final payload = jsonDecode(row['payload'] as String);
              if (payload is! Map) continue;
              final rawPartnerId = payload['user_id'] ?? payload['member_id'];
              final partnerId = rawPartnerId is int
                  ? rawPartnerId
                  : int.tryParse(rawPartnerId?.toString() ?? '') ?? 0;
              final accountId = row['account_id'] as int? ?? 0;
              if (accountId <= 0 || partnerId <= 0) continue;
              await db.insert(
                'chat_mute_preferences',
                {
                  'account_id': accountId,
                  'partner_user_id': partnerId,
                  'muted': 1,
                  'sync_pending': 1,
                  'updated_at': now,
                },
                conflictAlgorithm: ConflictAlgorithm.replace,
              );
            } catch (_) {
              // A malformed legacy cache row must not prevent the database upgrade.
            }
          }
        }

        if (oldVersion >= 2 && oldVersion < 3) {
          await db.execute(
            'ALTER TABLE chat_mute_preferences ADD COLUMN sync_pending INTEGER NOT NULL DEFAULT 0',
          );
          // Existing v2 local values may not yet exist on the server.
          await db.update(
            'chat_mute_preferences',
            {'sync_pending': 1},
          );
        }

        if (oldVersion < 4) {
          await _encryptLegacyPayloads(db);
        }
      },
    );
    return _database!;
  }

  static Future<void> saveThreads(
    int accountId,
    List<ChatThreadModel> threads,
  ) async {
    if (accountId <= 0) return;
    final db = await _db;
    await db.transaction((txn) async {
      await txn.delete(
        'chat_threads',
        where: 'account_id = ?',
        whereArgs: [accountId],
      );
      final batch = txn.batch();
      for (final thread in threads) {
        if (thread.id <= 0) continue;
        batch.insert(
          'chat_threads',
          {
            'account_id': accountId,
            'thread_id': thread.id,
            'payload': await _encryptPayload(jsonEncode(thread.toJson())),
            'updated_at': DateTime.now().millisecondsSinceEpoch,
          },
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      await batch.commit(noResult: true);
    });
  }

  static Future<List<ChatThreadModel>> getThreads(int accountId) async {
    if (accountId <= 0) return [];
    final rows = await (await _db).query(
      'chat_threads',
      columns: ['payload'],
      where: 'account_id = ?',
      whereArgs: [accountId],
      orderBy: 'updated_at DESC, thread_id DESC',
    );
    final result = <ChatThreadModel>[];
    for (final row in rows) {
      try {
        final decoded = jsonDecode(await _decryptPayload(row['payload'] as String));
        if (decoded is Map) {
          result.add(
            ChatThreadModel.fromJson(Map<String, dynamic>.from(decoded)),
          );
        }
      } catch (_) {
        // Skip a damaged row without hiding other cached conversations.
      }
    }
    return result;
  }

  static Future<void> saveMessages(
    int accountId,
    int threadId,
    List<ChatMessageModel> messages,
  ) async {
    if (accountId <= 0 || threadId <= 0 || messages.isEmpty) return;
    final db = await _db;
    await db.transaction((txn) async {
      final batch = txn.batch();
      for (final message in messages) {
        if (message.id <= 0) continue;
        batch.insert(
          'chat_messages',
          {
            'account_id': accountId,
            'thread_id': threadId,
            'message_id': message.id,
            'payload': await _encryptPayload(jsonEncode(message.toJson())),
          },
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      await batch.commit(noResult: true);
      await txn.rawDelete(
        '''
        DELETE FROM chat_messages
        WHERE account_id = ? AND thread_id = ?
          AND message_id NOT IN (
            SELECT message_id FROM chat_messages
            WHERE account_id = ? AND thread_id = ?
            ORDER BY message_id DESC LIMIT ?
          )
        ''',
        [
          accountId,
          threadId,
          accountId,
          threadId,
          maxMessagesPerThread,
        ],
      );
    });
  }

  static Future<List<ChatMessageModel>> getMessages(
    int accountId,
    int threadId, {
    int? currentUserId,
    int limit = maxMessagesPerThread,
  }) async {
    if (accountId <= 0 || threadId <= 0) return [];
    final rows = await (await _db).query(
      'chat_messages',
      columns: ['payload'],
      where: 'account_id = ? AND thread_id = ?',
      whereArgs: [accountId, threadId],
      orderBy: 'message_id ASC',
      limit: limit,
    );
    return _withoutHiddenMessages(
      accountId,
      threadId,
      await _decodeMessages(rows, currentUserId ?? accountId),
    );
  }

  static Future<Map<String, dynamic>> getOlderMessages(
    int accountId,
    int threadId,
    int firstMessageId,
    int partnerUserId, {
    int limit = 20,
  }) async {
    if (accountId <= 0 || threadId <= 0 || firstMessageId <= 0) {
      return {'messages': <ChatMessageModel>[], 'has_more': false};
    }
    final rows = await (await _db).query(
      'chat_messages',
      columns: ['payload'],
      where: 'account_id = ? AND thread_id = ? AND message_id < ?',
      whereArgs: [accountId, threadId, firstMessageId],
      orderBy: 'message_id DESC',
      limit: limit + 1,
    );
    final decodedMessages = await _decodeMessages(
      rows.take(limit).toList().reversed.toList(),
      accountId,
      partnerUserId: partnerUserId,
    );
    return {
      'messages': await _withoutHiddenMessages(accountId, threadId, decodedMessages),
      'has_more': rows.length > limit,
    };
  }

  static Future<List<ChatMessageModel>> _decodeMessages(
    List<Map<String, Object?>> rows,
    int currentUserId, {
    int partnerUserId = 0,
  }) async {
    final result = <ChatMessageModel>[];
    for (final row in rows) {
      try {
        final decoded = jsonDecode(await _decryptPayload(row['payload'] as String));
        if (decoded is Map) {
          result.add(
            ChatMessageModel.fromJson(
              Map<String, dynamic>.from(decoded),
              partnerUserId,
              currentUserId: currentUserId,
            ),
          );
        }
      } catch (_) {
        // Skip only the damaged message row.
      }
    }
    return result;
  }

  static Future<List<ChatMessageModel>> searchMessages(
    int accountId,
    int threadId,
    String query, {
    int currentUserId = 0,
    int limit = 100,
  }) async {
    final cleanQuery = query.trim();
    if (accountId <= 0 || threadId <= 0 || cleanQuery.isEmpty) return [];
    final rows = await (await _db).query(
      'chat_messages',
      columns: ['payload'],
      where: 'account_id = ? AND thread_id = ?',
      whereArgs: [accountId, threadId],
      orderBy: 'message_id DESC',
      limit: maxMessagesPerThread,
    );
    final decoded = await _decodeMessages(
      rows.reversed.toList(),
      currentUserId > 0 ? currentUserId : accountId,
    );
    final matches = decoded
        .where((message) => message.message.toLowerCase().contains(cleanQuery.toLowerCase()))
        .toList();
    final limited = matches.length > limit
        ? matches.sublist(matches.length - limit)
        : matches;
    return _withoutHiddenMessages(accountId, threadId, limited);
  }

  static Future<void> setMuted(
    int accountId,
    int partnerUserId,
    bool muted, {
    bool needsSync = true,
  }) async {
    if (accountId <= 0 || partnerUserId <= 0) return;
    final db = await _db;
    await db.insert(
      'chat_mute_preferences',
      {
        'account_id': accountId,
        'partner_user_id': partnerUserId,
        'muted': muted ? 1 : 0,
        'sync_pending': needsSync ? 1 : 0,
        'updated_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// Apply server state without overwriting an offline choice that still
  /// needs to be uploaded.
  static Future<void> setMutedFromServer(
    int accountId,
    int partnerUserId,
    bool muted,
  ) async {
    if (accountId <= 0 || partnerUserId <= 0) return;
    final db = await _db;
    await db.transaction((txn) async {
      final pending = await txn.query(
        'chat_mute_preferences',
        columns: ['sync_pending'],
        where: 'account_id = ? AND partner_user_id = ?',
        whereArgs: [accountId, partnerUserId],
        limit: 1,
      );
      if (pending.isNotEmpty && pending.first['sync_pending'] == 1) {
        return;
      }
      await txn.insert(
        'chat_mute_preferences',
        {
          'account_id': accountId,
          'partner_user_id': partnerUserId,
          'muted': muted ? 1 : 0,
          'sync_pending': 0,
          'updated_at': DateTime.now().millisecondsSinceEpoch,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  static Future<Map<int, bool>> getPendingMuteSettings(
    int accountId, {
    int? partnerUserId,
  }) async {
    if (accountId <= 0) return {};
    final whereArgs = <Object?>[accountId];
    var where = 'account_id = ? AND sync_pending = 1';
    if (partnerUserId != null) {
      where += ' AND partner_user_id = ?';
      whereArgs.add(partnerUserId);
    }
    final rows = await (await _db).query(
      'chat_mute_preferences',
      columns: ['partner_user_id', 'muted'],
      where: where,
      whereArgs: whereArgs,
    );
    return {
      for (final row in rows)
        row['partner_user_id'] as int: row['muted'] == 1,
    };
  }

  static Future<void> markMuteSynced(
    int accountId,
    int partnerUserId,
  ) async {
    if (accountId <= 0 || partnerUserId <= 0) return;
    await (await _db).update(
      'chat_mute_preferences',
      {'sync_pending': 0},
      where: 'account_id = ? AND partner_user_id = ?',
      whereArgs: [accountId, partnerUserId],
    );
  }

  static Future<bool> isMuted(int accountId, int partnerUserId) async {
    if (accountId <= 0 || partnerUserId <= 0) return false;
    final rows = await (await _db).query(
      'chat_mute_preferences',
      columns: ['muted'],
      where: 'account_id = ? AND partner_user_id = ?',
      whereArgs: [accountId, partnerUserId],
      limit: 1,
    );
    return rows.isNotEmpty && rows.first['muted'] == 1;
  }

  static Future<Map<int, bool>> getMuteSettings(int accountId) async {
    if (accountId <= 0) return {};
    final rows = await (await _db).query(
      'chat_mute_preferences',
      columns: ['partner_user_id', 'muted'],
      where: 'account_id = ?',
      whereArgs: [accountId],
    );
    return {
      for (final row in rows)
        row['partner_user_id'] as int: row['muted'] == 1,
    };
  }

  static Future<void> hideMessage(
    int accountId,
    int threadId,
    int messageId,
  ) async {
    if (accountId <= 0 || threadId <= 0 || messageId <= 0) return;
    final db = await _db;
    await db.insert(
      'chat_hidden_messages',
      {
        'account_id': accountId,
        'thread_id': threadId,
        'message_id': messageId,
        'hidden_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
    await db.delete(
      'chat_messages',
      where: 'account_id = ? AND thread_id = ? AND message_id = ?',
      whereArgs: [accountId, threadId, messageId],
    );
  }

  static Future<Set<int>> getHiddenMessageIds(
    int accountId,
    int threadId,
  ) async {
    if (accountId <= 0 || threadId <= 0) return <int>{};
    final rows = await (await _db).query(
      'chat_hidden_messages',
      columns: ['message_id'],
      where: 'account_id = ? AND thread_id = ?',
      whereArgs: [accountId, threadId],
    );
    return rows.map((row) => row['message_id'] as int).toSet();
  }

  static Future<List<ChatMessageModel>> _withoutHiddenMessages(
    int accountId,
    int threadId,
    List<ChatMessageModel> messages,
  ) async {
    final hiddenIds = await getHiddenMessageIds(accountId, threadId);
    if (hiddenIds.isEmpty) return messages;
    return messages.where((message) => !hiddenIds.contains(message.id)).toList();
  }

  static Future<void> saveScrollOffset(
    int accountId,
    int threadId,
    double offset,
  ) async {
    if (accountId <= 0 || threadId <= 0 || !offset.isFinite) return;
    final db = await _db;
    final updated = await db.update(
      'chat_thread_preferences',
      {'scroll_offset': offset < 0 ? 0.0 : offset},
      where: 'account_id = ? AND thread_id = ?',
      whereArgs: [accountId, threadId],
    );
    if (updated == 0) {
      await db.insert(
        'chat_thread_preferences',
        {
          'account_id': accountId,
          'thread_id': threadId,
          'muted': 0,
          'scroll_offset': offset < 0 ? 0.0 : offset,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
  }

  static Future<double?> getScrollOffset(
    int accountId,
    int threadId,
  ) async {
    if (accountId <= 0 || threadId <= 0) return null;
    final rows = await (await _db).query(
      'chat_thread_preferences',
      columns: ['scroll_offset'],
      where: 'account_id = ? AND thread_id = ?',
      whereArgs: [accountId, threadId],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    final value = rows.first['scroll_offset'];
    return value is num ? value.toDouble() : null;
  }

  static Future<void> deleteThread(int accountId, int threadId) async {
    if (accountId <= 0 || threadId <= 0) return;
    final db = await _db;
    await db.transaction((txn) async {
      await txn.delete(
        'chat_threads',
        where: 'account_id = ? AND thread_id = ?',
        whereArgs: [accountId, threadId],
      );
      await txn.delete(
        'chat_messages',
        where: 'account_id = ? AND thread_id = ?',
        whereArgs: [accountId, threadId],
      );
      await txn.delete(
        'chat_thread_preferences',
        where: 'account_id = ? AND thread_id = ?',
        whereArgs: [accountId, threadId],
      );
      await txn.delete(
        'chat_hidden_messages',
        where: 'account_id = ? AND thread_id = ?',
        whereArgs: [accountId, threadId],
      );
    });
  }

  static Future<void> clearAccount(int accountId) async {
    if (accountId <= 0) return;
    final db = await _db;
    await db.transaction((txn) async {
      await txn.delete('chat_threads',
          where: 'account_id = ?', whereArgs: [accountId]);
      await txn.delete('chat_messages',
          where: 'account_id = ?', whereArgs: [accountId]);
      await txn.delete('chat_thread_preferences',
          where: 'account_id = ?', whereArgs: [accountId]);
      await txn.delete('chat_mute_preferences',
          where: 'account_id = ?', whereArgs: [accountId]);
      await txn.delete('chat_hidden_messages',
          where: 'account_id = ?', whereArgs: [accountId]);
    });
  }
}
