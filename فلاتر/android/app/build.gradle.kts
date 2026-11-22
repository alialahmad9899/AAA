import java.util.Properties
import java.io.FileInputStream

plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
    // إضافة محرك خدمات جوجل وفايربيس بأسلوب Kotlin DSL
    id("com.google.gms.google-services")
}

val releaseSigningProperties = Properties()
val releaseSigningPropertiesFile = rootProject.file("key.properties")
if (releaseSigningPropertiesFile.exists()) {
    FileInputStream(releaseSigningPropertiesFile).use { releaseSigningProperties.load(it) }
}

fun releaseSigningValue(propertyName: String, environmentName: String): String? {
    return providers.environmentVariable(environmentName).orNull
        ?: releaseSigningProperties.getProperty(propertyName)
}

val releaseStoreFile = releaseSigningValue("storeFile", "WESAL_UPLOAD_STORE_FILE")
val releaseStorePassword = releaseSigningValue("storePassword", "WESAL_UPLOAD_STORE_PASSWORD")
val releaseKeyAlias = releaseSigningValue("keyAlias", "WESAL_UPLOAD_KEY_ALIAS")
val releaseKeyPassword = releaseSigningValue("keyPassword", "WESAL_UPLOAD_KEY_PASSWORD")


android {
    namespace = "com.example.wesal_app"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        // تفعيل ميزة Desugaring لدعم حزمة الإشعارات وميزات Java الحديثة
        isCoreLibraryDesugaringEnabled = true

        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        // Application ID المطابق لملف google-services.json
        applicationId = "com.example.wesal_app"
        
        // الحد الأدنى لدعم ميزات Firebase
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        create("release") {
            // Secrets are supplied through environment variables or a local,
            // git-ignored android/key.properties file. Never commit a keystore.
            storeFile = releaseStoreFile?.let { file(it) }
            storePassword = releaseStorePassword
            keyAlias = releaseKeyAlias
            keyPassword = releaseKeyPassword
        }
    }

    buildTypes {
        release {
            // Release artifacts must never fall back to the publicly known debug key.
            signingConfig = signingConfigs.getByName("release")

            // تفعيل ضغط وتشفير الكود وربط ملف قواعد ProGuard
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}

dependencies {
    // مكتبة التوافق المطلوبة لتفعيل ميزة Desugaring بأسلوب Kotlin DSL
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.0.4")
}