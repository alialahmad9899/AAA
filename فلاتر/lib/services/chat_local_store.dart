// Persistent account-scoped chat cache for offline reading and chat preferences.
import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import '../models/chat_model.dart';

class ChatLocalStore {
  ChatLocalStore._();

  static Database? _database;
  static const int maxMessagesPerThread = 2000;

  static Future<Database> get _db async {
    if (_database != null) return _database!;
    final databasePath = await getDatabasesPath();
    final path = '$databasePath/wesal_chat_cache.db';
    _database = await openDatabase(
      path,
      version: 2,
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
                  'updated_at': now,
                },
                conflictAlgorithm: ConflictAlgorithm.replace,
              );
            } catch (_) {
              // A malformed legacy cache row must not prevent the database upgrade.
            }
          }
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
            'payload': jsonEncode(thread.toJson()),
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
        final decoded = jsonDecode(row['payload'] as String);
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
            'payload': jsonEncode(message.toJson()),
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
      _decodeMessages(rows, currentUserId ?? accountId),
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
    final decodedMessages = _decodeMessages(
      rows.take(limit).toList().reversed.toList(),
      accountId,
      partnerUserId: partnerUserId,
    );
    return {
      'messages': await _withoutHiddenMessages(accountId, threadId, decodedMessages),
      'has_more': rows.length > limit,
    };
  }

  static List<ChatMessageModel> _decodeMessages(
    List<Map<String, Object?>> rows,
    int currentUserId, {
    int partnerUserId = 0,
  }) {
    final result = <ChatMessageModel>[];
    for (final row in rows) {
      try {
        final decoded = jsonDecode(row['payload'] as String);
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
      where: 'account_id = ? AND thread_id = ? AND payload LIKE ?',
      whereArgs: [accountId, threadId, '%$cleanQuery%'],
      orderBy: 'message_id DESC',
      limit: limit,
    );
    return _withoutHiddenMessages(
      accountId,
      threadId,
      _decodeMessages(
        rows.reversed.toList(),
        currentUserId > 0 ? currentUserId : accountId,
      ),
    );
  }

  static Future<void> setMuted(
    int accountId,
    int partnerUserId,
    bool muted,
  ) async {
    if (accountId <= 0 || partnerUserId <= 0) return;
    final db = await _db;
    await db.insert(
      'chat_mute_preferences',
      {
        'account_id': accountId,
        'partner_user_id': partnerUserId,
        'muted': muted ? 1 : 0,
        'updated_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
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
