package com.arash.altafi.websocket4

import androidx.appcompat.app.AppCompatActivity
import android.os.Bundle
import android.util.Log
import com.arash.altafi.websocket4.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private val binding by lazy {
        ActivityMainBinding.inflate(layoutInflater)
    }
    private var webSocket: WebSocketClient? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(binding.root)

        init()
    }

    private fun init() = binding.apply {
        setupWebSocket()

        edtMessage.setOnEditorActionListener { _, _, _ ->
            btnSend.performClick()
            false
        }

        btnSend.setOnClickListener {
            if (edtMessage.text.toString().isEmpty()) {
                edtMessage.error = "Please Fill Message"
            } else {
                webSocket?.send(edtMessage.text.toString())
                edtMessage.text?.clear()
            }
        }
    }

    private fun setupWebSocket() = binding.apply {
        webSocket = WebSocketClient(URL) { message, isSuccess ->
            runOnUiThread {
                // Update textview with incoming message
                tvMessage.text = message
                chkStatus.isChecked = isSuccess
                Log.i("WebSocketClient", "isSuccess: $isSuccess")
                Log.i("WebSocketClient", "message: $message")
            }
        }
        webSocket?.connect()
    }

    override fun onDestroy() {
        super.onDestroy()
        webSocket?.disconnect()
    }

    private companion object {
        const val URL = "ws://192.168.1.101:8080"
    }

}