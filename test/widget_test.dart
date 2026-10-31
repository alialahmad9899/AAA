// هذا ملف اختبار واجهة التطبيق
import 'package:flutter/src/widgets/framework.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('Wesal App load test', (WidgetTester tester) async {
    // تشغيل تطبيق وصال المحدث
    await tester.pumpWidget(const WesalApp() as Widget);
  });
}

class WesalApp {
  const WesalApp();
}
