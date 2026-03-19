package com.betting.executor.domain.executor

import timber.log.Timber
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

/**
 * Verifies HMAC-SHA256 signatures on server messages.
 * The backend signs round.prepared messages to prevent tampering.
 */
class SignatureVerifier(private val hmacKey: String) {

    /**
     * Verifies that the given data matches the expected HMAC-SHA256 signature.
     * @param data The raw message data (JSON without the signature field)
     * @param signature The hex-encoded HMAC-SHA256 signature from the server
     * @return true if the signature is valid
     */
    fun verify(data: String, signature: String): Boolean {
        return try {
            val calculated = calculate(data)
            val valid = calculated.equals(signature, ignoreCase = true)
            if (!valid) {
                Timber.w("Signature mismatch: expected=%s, calculated=%s", signature, calculated)
            }
            valid
        } catch (e: Exception) {
            Timber.e(e, "Signature verification failed")
            false
        }
    }

    /**
     * Calculates HMAC-SHA256 for the given data.
     * @return Hex-encoded HMAC-SHA256 hash
     */
    fun calculate(data: String): String {
        val mac = Mac.getInstance("HmacSHA256")
        val secretKey = SecretKeySpec(hmacKey.toByteArray(Charsets.UTF_8), "HmacSHA256")
        mac.init(secretKey)
        val hash = mac.doFinal(data.toByteArray(Charsets.UTF_8))
        return hash.joinToString("") { "%02x".format(it) }
    }
}
