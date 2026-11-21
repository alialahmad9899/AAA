package com.example.wesal_app

import android.view.WindowManager
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterFragmentActivity() {
    private val chatSecurityChannel = "com.wesalsyria.wesal/chat_security"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

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
