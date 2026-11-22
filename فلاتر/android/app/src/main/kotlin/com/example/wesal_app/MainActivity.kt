package com.example.wesal_app

import android.content.Intent
import android.net.Uri
import android.provider.Settings
import android.view.WindowManager
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterFragmentActivity() {
    private val chatSecurityChannel = "com.wesalsyria.wesal/chat_security"
    private val notificationSettingsChannel = "com.wesalsyria.wesal/notification_settings"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            notificationSettingsChannel
        ).setMethodCallHandler { call, result ->
            if (call.method != "openAppNotificationSettings") {
                result.notImplemented()
                return@setMethodCallHandler
            }

            try {
                val notificationIntent = Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS).apply {
                    putExtra(Settings.EXTRA_APP_PACKAGE, packageName)
                }
                val intent = if (notificationIntent.resolveActivity(packageManager) != null) {
                    notificationIntent
                } else {
                    Intent(
                        Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                        Uri.fromParts("package", packageName, null)
                    )
                }
                startActivity(intent)
                result.success(true)
            } catch (error: Exception) {
                result.error(
                    "notification_settings_unavailable",
                    "Unable to open application notification settings.",
                    null
                )
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            chatSecurityChannel
        ).setMethodCallHandler { call, result ->
            if (call.method != "setSecure") {
                result.notImplemented()
                return@setMethodCallHandler
            }

            val enabled = call.argument<Boolean>("enabled") ?: false
            val secureFlag = WindowManager.LayoutParams.FLAG_SECURE
            val activityWindow = window

            if (enabled) {
                activityWindow.setFlags(secureFlag, secureFlag)
            } else {
                activityWindow.clearFlags(secureFlag)
            }

            result.success(true)
        }
    }
}
