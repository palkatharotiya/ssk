package `in`.sskstore.kalakunj.data.repository

import `in`.sskstore.kalakunj.data.api.SSKApiService
import `in`.sskstore.kalakunj.data.model.*

sealed class Result<out T> {
    data class Success<T>(val data: T) : Result<T>()
    data class Error(val message: String) : Result<Nothing>()
    data object Loading : Result<Nothing>()
}

class SSKRepository(
    private val api: SSKApiService = SSKApiService.create()
) {
    suspend fun getHomeData(): Result<HomeData> = safeCall {
        val response = api.getHomeData()
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Failed to load home data")
        }
    }

    suspend fun getProducts(
        dept: Int? = null,
        category: Int? = null,
        search: String? = null,
        onSale: Boolean = false,
        sort: String? = null,
        page: Int = 1
    ): Result<ProductsData> = safeCall {
        val response = api.getProducts(
            dept = dept,
            category = category,
            search = search?.takeIf { it.isNotBlank() },
            onSale = if (onSale) "1" else null,
            sort = sort,
            page = page
        )
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Failed to load products")
        }
    }

    suspend fun getProductDetail(productId: Int): Result<ProductDetailData> = safeCall {
        val response = api.getProductDetail(productId)
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Product not found")
        }
    }

    suspend fun login(email: String, password: String): Result<AuthData> = safeCall {
        val response = api.auth(mapOf(
            "action" to "login",
            "email" to email,
            "password" to password
        ))
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Login failed")
        }
    }

    suspend fun register(
        firstName: String, lastName: String,
        email: String, password: String, phone: String
    ): Result<AuthData> = safeCall {
        val response = api.auth(mapOf(
            "action" to "register",
            "first_name" to firstName,
            "last_name" to lastName,
            "email" to email,
            "password" to password,
            "phone" to phone
        ))
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Registration failed")
        }
    }

    suspend fun forgotPassword(email: String): Result<String> = safeCall {
        val response = api.auth(mapOf(
            "action" to "forgot_password",
            "email" to email
        ))
        response.body()?.message ?: "If an account exists, reset instructions have been sent."
    }

    suspend fun createOrder(request: OrderRequest): Result<OrderData> = safeCall {
        val response = api.createOrder(request)
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Order creation failed")
        }
    }

    suspend fun trackOrder(orderNumber: String): Result<OrderTrackData> = safeCall {
        val response = api.trackOrder(orderNumber)
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Order not found")
        }
    }

    suspend fun getStoreInfo(): Result<StoreInfoData> = safeCall {
        val response = api.getStoreInfo()
        if (response.isSuccessful && response.body()?.success == true) {
            response.body()!!.data!!
        } else {
            throw Exception(response.body()?.message ?: "Failed to load store info")
        }
    }

    private suspend fun <T> safeCall(block: suspend () -> T): Result<T> {
        return try {
            Result.Success(block())
        } catch (e: Exception) {
            Result.Error(e.message ?: "An unexpected error occurred. Please try again.")
        }
    }
}
