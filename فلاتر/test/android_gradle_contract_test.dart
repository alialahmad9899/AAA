import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  final rootGradle = File('android/build.gradle.kts').readAsStringSync();
  final appGradle = File('android/app/build.gradle.kts').readAsStringSync();
  final settingsGradle = File('android/settings.gradle.kts').readAsStringSync();
  final wrapperProperties =
      File('android/gradle/wrapper/gradle-wrapper.properties')
          .readAsStringSync();

  group('Android Gradle configuration contract', () {
    test(
      'root Android Gradle file does not declare the Kotlin extension',
      () {
        expect(
          RegExp(r'(?m)^\\s*kotlin\\s*\\{').hasMatch(rootGradle),
          isFalse,
        );
      },
    );

    test('Kotlin Android plugin is applied to the app module', () {
      expect(appGradle, contains('id("org.jetbrains.kotlin.android")'));
      expect(settingsGradle, contains(
        'id("org.jetbrains.kotlin.android") version "2.2.20" apply false',
      ));
    });

    test('Kotlin bytecode target is aligned with Java 17', () {
      expect(
        appGradle,
        contains(
          'jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17)',
        ),
      );
      expect(appGradle, contains('sourceCompatibility = JavaVersion.VERSION_17'));
      expect(appGradle, contains('targetCompatibility = JavaVersion.VERSION_17'));
    });

    test('Gradle and Kotlin plugin versions remain compatible', () {
      expect(
        wrapperProperties,
        contains('distributionUrl=https\\://services.gradle.org/distributions/gradle-8.14-all.zip'),
      );
      expect(
        settingsGradle,
        contains('id("org.jetbrains.kotlin.android") version "2.2.20" apply false'),
      );
    });
  });
}
