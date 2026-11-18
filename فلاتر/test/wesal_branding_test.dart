import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wesal_app/widgets/wesal_logo.dart';

void main() {
  testWidgets('Wesal logo uses the registered original transparent asset',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Center(
            child: WesalLogo(width: 120, height: 120),
          ),
        ),
      ),
    );

    final Image logo = tester.widget<Image>(find.byType(Image));
    expect(logo.image, isA<AssetImage>());
    expect((logo.image as AssetImage).assetName, 'assets/images/wesal_logo.png');
    expect(logo.width, 120);
    expect(logo.height, 120);
    expect(logo.semanticLabel, 'شعار وصال');
  });
}
