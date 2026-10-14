package `in`.sskstore.kalakunj.data.api

import `in`.sskstore.kalakunj.BuildConfig
import `in`.sskstore.kalakunj.data.model.*
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.*
import java.util.concurrent.TimeUnit

interface SSKApiService {

    // Home
    @GET("home.php")
    suspend fun getHomeData(): Response<ApiResponse<HomeData>>

    // Products
    @GET("products.php")
    suspend fun getProducts(
        @Query("dept") dept: Int? = null,
        @Query("category") category: Int? = null,
        @Query("search") search: String? = null,
        @Query("on_sale") onSale: String? = null,
        @Query("sort") sort: String? = null,
        @Query("page") page: Int = 1,
        @Query("limit") limit: Int = 20
    ): Response<ApiResponse<ProductsData>>

    // Product Detail
    @GET("product.php")
    suspend fun getProductDetail(
        @Query("id") productId: Int
    ): Response<ApiResponse<ProductDetailData>>

    // Auth
    @POST("auth.php")
    suspend fun auth(
        @Body body: Map<String, String>
    ): Response<ApiResponse<AuthData>>

    // Create Order
    @POST("orders.php")
    suspend fun createOrder(
        @Body orderRequest: OrderRequest
    ): Response<ApiResponse<OrderData>>

    // Track Order
    @GET("orders.php")
    suspend fun trackOrder(
        @Query("track") orderNumber: String
    ): Response<ApiResponse<OrderTrackData>>

    // Customer Orders
    @GET("orders.php")
    suspend fun getCustomerOrders(
        @Header("Authorization") token: String
    ): Response<ApiResponse<Map<String, List<OrderSummary>>>>

    // Store Info
    @GET("store_info.php")
    suspend fun getStoreInfo(): Response<ApiResponse<StoreInfoData>>

    companion object {
        fun create(): SSKApiService {
            val loggingInterceptor = HttpLoggingInterceptor().apply {
                level = if (BuildConfig.DEBUG) HttpLoggingInterceptor.Level.BODY
                        else HttpLoggingInterceptor.Level.NONE
            }

            val client = OkHttpClient.Builder()
                .addInterceptor(loggingInterceptor)
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build()

            return Retrofit.Builder()
                .baseUrl(BuildConfig.API_BASE_URL)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SSKApiService::class.java)
        }
    }
}
