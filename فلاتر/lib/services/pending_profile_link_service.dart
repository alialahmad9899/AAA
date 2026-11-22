import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../screens/member_profile_screen.dart';

/// Holds one profile deep link until an authenticated home screen is ready.
class PendingProfileLinkService {
  PendingProfileLinkService._();

  static const String _pendingMemberIdKey = 'pending_profile_deep_link_id';
  static bool _authenticatedHomeReady = false;
  static bool _openingPendingRoute = false;

  static bool get isAuthenticatedHomeReady => _authenticatedHomeReady;

  static void markAuthenticatedHomeReady() {
    _authenticatedHomeReady = true;
  }

  static void markAuthenticatedHomeNotReady() {
    _authenticatedHomeReady = false;
  }

  static Future<void> save(int memberReference) async {
    if (memberReference <= 0) return;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_pendingMemberIdKey, memberReference);
  }

  static Future<void> openPending(BuildContext context) async {
    if (!context.mounted || !_authenticatedHomeReady || _openingPendingRoute) {
      return;
    }
    _openingPendingRoute = true;
    try {
      final prefs = await SharedPreferences.getInstance();
      final memberReference = prefs.getInt(_pendingMemberIdKey);
      if (memberReference == null || memberReference <= 0) return;

      await prefs.remove(_pendingMemberIdKey);
      if (!context.mounted || !_authenticatedHomeReady) {
        // Keep the reference if the authenticated route disappeared meanwhile.
        await prefs.setInt(_pendingMemberIdKey, memberReference);
        return;
      }
      await Navigator.of(context, rootNavigator: true).push(
        MaterialPageRoute(
          builder: (_) => MemberProfileScreen(userId: memberReference),
        ),
      );
    } finally {
      _openingPendingRoute = false;
    }
  }
}
