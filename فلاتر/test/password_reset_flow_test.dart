import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:wesal_app/screens/change_password_screen.dart';
import 'package:wesal_app/screens/forgot_password_screen.dart';

void main() {
  group('ForgotPasswordScreen', () {
    testWidgets('requests a code and submits the reset details', (tester) async {
      String? requested;
      String? submittedEmail;
      String? submittedCode;
      String? submittedPassword;

      await tester.pumpWidget(MaterialApp(home: ForgotPasswordScreen(
        onRequestCode: (email) async {
          requested = email;
          return {'success': true};
        },
        onResetPassword: (email, code, password, confirmation) async {
          submittedEmail = email;
          submittedCode = code;
          submittedPassword = password;
          expect(confirmation, password);
          return {'success': true};
        },
      )));

      await tester.enterText(find.byKey(const Key('password-reset-email')), ' USER@example.com ');
      await tester.ensureVisible(find.byKey(const Key('password-reset-send-code')));
      await tester.tap(find.byKey(const Key('password-reset-send-code')));
      await tester.pumpAndSettle();
      expect(requested, 'user@example.com');

      await tester.enterText(find.byKey(const Key('password-reset-code')), '123456');
      await tester.enterText(find.byKey(const Key('password-reset-password')), 'NewPass123');
      await tester.enterText(find.byKey(const Key('password-reset-confirmation')), 'NewPass123');
      await tester.ensureVisible(find.byKey(const Key('password-reset-send-code')));
      await tester.tap(find.byKey(const Key('password-reset-send-code')));
      await tester.pumpAndSettle();

      expect(submittedEmail, 'user@example.com');
      expect(submittedCode, '123456');
      expect(submittedPassword, 'NewPass123');
      expect(find.byKey(const Key('password-reset-back-to-login')), findsOneWidget);
    });

    testWidgets('shows visible progress and persistent success while requesting a code', (tester) async {
      final completer = Completer<Map<String, dynamic>>();
      await tester.pumpWidget(MaterialApp(home: ForgotPasswordScreen(
        onRequestCode: (_) => completer.future,
      )));
      await tester.enterText(find.byKey(const Key('password-reset-email')), 'user@example.com');
      await tester.ensureVisible(find.byKey(const Key('password-reset-send-code')));
      await tester.tap(find.byKey(const Key('password-reset-send-code')));
      await tester.pump();
      expect(find.text('جارٍ إرسال طلب الاستعادة...'), findsOneWidget);
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      completer.complete({'success': true});
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('password-reset-status')), findsOneWidget);
      expect(find.textContaining('إذا كان البريد مسجلاً'), findsOneWidget);
      expect(find.byKey(const Key('password-reset-code')), findsOneWidget);
    });

    testWidgets('keeps a visible localized error when requesting a code fails', (tester) async {
      await tester.pumpWidget(MaterialApp(home: ForgotPasswordScreen(
        onRequestCode: (_) async => {'success': false},
      )));
      await tester.enterText(find.byKey(const Key('password-reset-email')), 'user@example.com');
      await tester.ensureVisible(find.byKey(const Key('password-reset-send-code')));
      await tester.tap(find.byKey(const Key('password-reset-send-code')));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('password-reset-status')), findsOneWidget);
      expect(find.text('تعذر إرسال رمز الاستعادة. تحقق من البريد وحاول مجدداً.'), findsOneWidget);
    });

    testWidgets('rejects an invalid email without calling the API', (tester) async {
      var requests = 0;
      await tester.pumpWidget(MaterialApp(home: ForgotPasswordScreen(
        onRequestCode: (_) async {
          requests++;
          return {'success': true};
        },
      )));
      await tester.enterText(find.byKey(const Key('password-reset-email')), 'not-an-email');
      await tester.ensureVisible(find.byKey(const Key('password-reset-send-code')));
      await tester.tap(find.byKey(const Key('password-reset-send-code')));
      await tester.pumpAndSettle();
      expect(requests, 0);
      expect(find.text('أدخل بريداً إلكترونياً صحيحاً'), findsOneWidget);
    });
  });

  group('ChangePasswordScreen', () {
    testWidgets('sends old password, new password and confirmation', (tester) async {
      String? oldValue, newValue, confirmValue;
      await tester.pumpWidget(MaterialApp(home: ChangePasswordScreen(
        onSubmit: (oldPassword, password, confirmation) async {
          oldValue = oldPassword;
          newValue = password;
          confirmValue = confirmation;
          return {'success': true};
        },
      )));
      await tester.enterText(find.byKey(const Key('change-password-old')), 'OldPass123');
      await tester.enterText(find.byKey(const Key('change-password-new')), 'NewPass123');
      await tester.enterText(find.byKey(const Key('change-password-confirmation')), 'NewPass123');
      await tester.ensureVisible(find.byKey(const Key('change-password-submit')));
      await tester.tap(find.byKey(const Key('change-password-submit')));
      await tester.pumpAndSettle();
      expect(oldValue, 'OldPass123');
      expect(newValue, 'NewPass123');
      expect(confirmValue, 'NewPass123');
      expect(find.text('تم تحديث كلمة المرور بنجاح'), findsOneWidget);
    });

    testWidgets('rejects reusing the current password', (tester) async {
      var submissions = 0;
      await tester.pumpWidget(MaterialApp(home: ChangePasswordScreen(
        onSubmit: (_, __, ___) async {
          submissions++;
          return {'success': true};
        },
      )));
      await tester.enterText(find.byKey(const Key('change-password-old')), 'SamePass123');
      await tester.enterText(find.byKey(const Key('change-password-new')), 'SamePass123');
      await tester.enterText(find.byKey(const Key('change-password-confirmation')), 'SamePass123');
      await tester.ensureVisible(find.byKey(const Key('change-password-submit')));
      await tester.tap(find.byKey(const Key('change-password-submit')));
      await tester.pumpAndSettle();
      expect(submissions, 0);
      expect(find.text('اختر كلمة مرور مختلفة عن الحالية'), findsOneWidget);
    });
  });
}
