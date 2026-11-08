import 'package:flutter/material.dart';

/// Maps catalogue icon keys from Laravel to native Material icons.
class VipServiceIcon {
  const VipServiceIcon._();

  static IconData fromKey(String? key) {
    switch (key) {
      case 'crown':
        return Icons.workspace_premium_rounded;
      case 'campaign':
        return Icons.campaign_rounded;
      case 'video':
        return Icons.movie_creation_rounded;
      case 'search':
        return Icons.search_rounded;
      case 'user_search':
        return Icons.person_search_rounded;
      case 'handshake':
        return Icons.handshake_rounded;
      case 'message':
        return Icons.chat_rounded;
      case 'shield':
        return Icons.security_rounded;
      case 'gift':
        return Icons.card_giftcard_rounded;
      case 'link':
        return Icons.link_rounded;
      case 'support':
        return Icons.support_agent_rounded;
      case 'star':
        return Icons.star_rounded;
      default:
        return Icons.auto_awesome_rounded;
    }
  }
}
