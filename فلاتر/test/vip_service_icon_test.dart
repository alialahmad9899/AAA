import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/utils/vip_service_icon.dart';

void main() {
  group('VipServiceIcon', () {
    test('maps every catalogue icon key to a Material icon', () {
      expect(VipServiceIcon.fromKey('crown'), Icons.workspace_premium_rounded);
      expect(VipServiceIcon.fromKey('campaign'), Icons.campaign_rounded);
      expect(VipServiceIcon.fromKey('video'), Icons.movie_creation_rounded);
      expect(VipServiceIcon.fromKey('search'), Icons.search_rounded);
      expect(VipServiceIcon.fromKey('user_search'), Icons.person_search_rounded);
      expect(VipServiceIcon.fromKey('handshake'), Icons.handshake_rounded);
      expect(VipServiceIcon.fromKey('message'), Icons.chat_rounded);
      expect(VipServiceIcon.fromKey('shield'), Icons.security_rounded);
      expect(VipServiceIcon.fromKey('gift'), Icons.card_giftcard_rounded);
      expect(VipServiceIcon.fromKey('link'), Icons.link_rounded);
      expect(VipServiceIcon.fromKey('support'), Icons.support_agent_rounded);
      expect(VipServiceIcon.fromKey('star'), Icons.star_rounded);
    });

    test('falls back safely for missing or unknown icon keys', () {
      expect(VipServiceIcon.fromKey(null), Icons.auto_awesome_rounded);
      expect(VipServiceIcon.fromKey('unknown'), Icons.auto_awesome_rounded);
    });
  });
}
