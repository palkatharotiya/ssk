package `in`.sskstore.kalakunj.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.*
import androidx.datastore.preferences.preferencesDataStore
import `in`.sskstore.kalakunj.data.model.CartItem
import `in`.sskstore.kalakunj.data.model.Customer
import `in`.sskstore.kalakunj.data.model.Product
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "ssk_prefs")

class SSKPreferences(private val context: Context) {

    private val gson = Gson()

    companion object {
        val AUTH_TOKEN = stringPreferencesKey("auth_token")
        val CUSTOMER_JSON = stringPreferencesKey("customer_json")
        val CART_JSON = stringPreferencesKey("cart_json")
        val WISHLIST_JSON = stringPreferencesKey("wishlist_json")
        val DARK_MODE = booleanPreferencesKey("dark_mode")
        val IS_FIRST_LAUNCH = booleanPreferencesKey("is_first_launch")
    }

    // === Auth ===
    val authToken: Flow<String?> = context.dataStore.data.map { it[AUTH_TOKEN] }

    val customer: Flow<Customer?> = context.dataStore.data.map { prefs ->
        prefs[CUSTOMER_JSON]?.let {
            try { gson.fromJson(it, Customer::class.java) } catch (e: Exception) { null }
        }
    }

    suspend fun saveAuth(token: String, customer: Customer) {
        context.dataStore.edit { prefs ->
            prefs[AUTH_TOKEN] = token
            prefs[CUSTOMER_JSON] = gson.toJson(customer)
        }
    }

    suspend fun clearAuth() {
        context.dataStore.edit { prefs ->
            prefs.remove(AUTH_TOKEN)
            prefs.remove(CUSTOMER_JSON)
        }
    }

    // === Cart ===
    val cartItems: Flow<List<CartItem>> = context.dataStore.data.map { prefs ->
        prefs[CART_JSON]?.let {
            try {
                val type = object : TypeToken<List<CartItem>>() {}.type
                gson.fromJson<List<CartItem>>(it, type)
            } catch (e: Exception) { emptyList() }
        } ?: emptyList()
    }

    suspend fun saveCart(items: List<CartItem>) {
        context.dataStore.edit { prefs ->
            prefs[CART_JSON] = gson.toJson(items)
        }
    }

    // === Wishlist ===
    val wishlistIds: Flow<Set<Int>> = context.dataStore.data.map { prefs ->
        prefs[WISHLIST_JSON]?.let {
            try {
                val type = object : TypeToken<Set<Int>>() {}.type
                gson.fromJson<Set<Int>>(it, type)
            } catch (e: Exception) { emptySet() }
        } ?: emptySet()
    }

    suspend fun saveWishlist(ids: Set<Int>) {
        context.dataStore.edit { prefs ->
            prefs[WISHLIST_JSON] = gson.toJson(ids)
        }
    }

    // === Theme ===
    val isDarkMode: Flow<Boolean> = context.dataStore.data.map { it[DARK_MODE] ?: false }

    suspend fun setDarkMode(enabled: Boolean) {
        context.dataStore.edit { it[DARK_MODE] = enabled }
    }

    // === First Launch ===
    val isFirstLaunch: Flow<Boolean> = context.dataStore.data.map { it[IS_FIRST_LAUNCH] ?: true }

    suspend fun setFirstLaunchDone() {
        context.dataStore.edit { it[IS_FIRST_LAUNCH] = false }
    }
}
