pluginManagement {
    val flutterSdkPath = run {
        val properties = java.util.Properties()
        file("local.properties").inputStream().use { properties.load(it) }
        val flutterSdkPath = properties.getProperty("flutter.sdk")
        require(flutterSdkPath != null) { "flutter.sdk not set in local.properties" }
        flutterSdkPath
    }

    includeBuild("$flutterSdkPath/packages/flutter_tools/gradle")

    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

plugins {
    id("dev.flutter.flutter-plugin-loader") version "1.0.0"
    // 🛑 تحديث إصدار Android Gradle Plugin إلى 8.11.1 لتلبية متطلبات AndroidX الحديثة 🛑
    id("com.android.application") version "8.11.1" apply false
    // 🛑 تحديث محرك Kotlin إلى 2.1.0 لمنع تعارضات وتطابق الإصدارات 🛑
    id("org.jetbrains.kotlin.android") version "2.1.0" apply false
    // تحديث خدمات Google و Firebase للإصدار الأحدث المستقر
    id("com.google.gms.google-services") version "4.4.2" apply false
}

include(":app")