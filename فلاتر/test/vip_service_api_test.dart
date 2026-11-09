import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/services/vip_service_api.dart';

void main() {
  group('VIP service catalogue response parsing', () {
    test('uses only the services returned by the server', () {
      final response = VipServiceApi.parseServicesListResponse(
        statusCode: 200,
        body: '''
          {
            "result": true,
            "data": [
              {
                "service_type": "custom_service",
                "title_ar": "خدمة مخصصة",
                "active": true,
                "sort_order": 7
              }
            ]
          }
        ''',
      );

      expect(response['success'], isTrue);
      final services = response['data'] as List;
      expect(services, hasLength(1));
      expect(services.single['service_type'], 'custom_service');
      expect(
        services.any((item) => item['service_type'] == 'direct_contact'),
        isFalse,
      );
    });

    test('sorts returned services by sort_order instead of internal IDs', () {
      final services = VipServiceApi.sortServicesByOrder([
        {'service_type': 'last', 'id': 1, 'sort_order': 30},
        {'service_type': 'first', 'id': 99, 'sort_order': 10},
        {'service_type': 'middle', 'id': 2, 'sort_order': 20},
      ]);

      expect(
        services.map((service) => service['service_type']).toList(),
        ['first', 'middle', 'last'],
      );
    });

    test('keeps received order stable when sort values are equal', () {
      final services = VipServiceApi.sortServicesByOrder([
        {'service_type': 'second', 'sort_order': 10},
        {'service_type': 'first', 'sort_order': 10},
      ]);

      expect(
        services.map((service) => service['service_type']).toList(),
        ['second', 'first'],
      );
    });

    test('keeps an intentionally empty server catalogue empty', () {
      final response = VipServiceApi.parseServicesListResponse(
        statusCode: 200,
        body: '{"result":true,"data":[]}',
      );

      expect(response['success'], isTrue);
      expect(response['data'], isEmpty);
    });

    test('does not substitute local services when the server rejects the request', () {
      final response = VipServiceApi.parseServicesListResponse(
        statusCode: 403,
        body: '{"result":false,"message":"Forbidden"}',
      );

      expect(response['success'], isFalse);
      expect(response['data'], isEmpty);
      expect(response['message'], 'Forbidden');
      expect(response.containsKey('is_fallback'), isFalse);
    });

    test('rejects malformed service items instead of silently hiding them', () {
      final response = VipServiceApi.parseServicesListResponse(
        statusCode: 200,
        body: '{"result":true,"data":[{"service_type":"ok"}, "invalid"]}',
      );

      expect(response['success'], isFalse);
      expect(response['data'], isEmpty);
    });
  });
}
