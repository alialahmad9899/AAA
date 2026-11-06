# Stripe & Push Provisioning missing rules fix
-dontwarn com.stripe.android.pushProvisioning.**
-dontwarn com.reactnativestripesdk.**

# Google Play Core / Deferred Components missing rules fix
-dontwarn com.google.android.play.core.**

# Flutter standard keep rules
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.autofill.** { *; }
-keep class io.flutter.embedding.** { *; }