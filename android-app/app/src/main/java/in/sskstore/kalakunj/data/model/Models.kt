package `in`.sskstore.kalakunj.data.model

import com.google.gson.annotations.SerializedName

// === API Response Wrappers ===

data class ApiResponse<T>(
    val success: Boolean,
    val message: String? = null,
    val data: T? = null
)

// === Home Screen Models ===

data class HomeData(
    val banners: List<Banner>,
    val departments: List<Department>,
    @SerializedName("featured_products") val featuredProducts: List<Product>,
    @SerializedName("on_sale_products") val onSaleProducts: List<Product>,
    @SerializedName("new_arrivals") val newArrivals: List<Product> = emptyList(),
    val store: StoreBasic? = null
)

data class Banner(
    val id: Int,
    val title: String?,
    val subtitle: String?,
    val image: String?,
    @SerializedName("image_url") val imageUrl: String,
    val link: String?,
    @SerializedName("sort_order") val sortOrder: Int = 0
)

data class Department(
    val id: Int,
    val name: String,
    val slug: String,
    val description: String? = null,
    val image: String? = null,
    val categories: List<Category> = emptyList()
) {
    val imageUrl: String? get() = image
}

data class Category(
    val id: Int,
    val name: String,
    val slug: String,
    @SerializedName("department_id") val departmentId: Int = 0
)

data class StoreBasic(
    val name: String = "Shri Sahajanand Sahitya Kendra",
    val tagline: String = "Kalakunj Mandir, Surat",
    @SerializedName("currency_symbol") val currencySymbol: String = "₹",
    @SerializedName("whatsapp_url") val whatsappUrl: String = "https://wa.me/919426551098"
)

// === Product Models ===

data class Product(
    val id: Int,
    val name: String,
    val slug: String? = null,
    @SerializedName("short_description") val shortDescription: String? = null,
    val description: String? = null,
    val price: Double,
    @SerializedName("sale_price") val salePrice: Double? = null,
    @SerializedName("stock_qty") val stockQty: Int = 0,
    val image: String? = null,
    @SerializedName("image_url") val imageUrl: String = "",
    @SerializedName("is_featured") val isFeatured: Int = 0,
    @SerializedName("on_sale") val onSale: Int = 0,
    @SerializedName("has_discount") val hasDiscount: Boolean = false,
    @SerializedName("discount_percent") val discountPercent: Int = 0,
    @SerializedName("department_name") val departmentName: String? = null,
    @SerializedName("department_slug") val departmentSlug: String? = null,
    @SerializedName("category_name") val categoryName: String? = null,
    @SerializedName("department_id") val departmentId: Int = 0,
    @SerializedName("category_id") val categoryId: Int = 0,
    @SerializedName("min_order_qty") val minOrderQty: Int = 1,
    @SerializedName("gallery_images") val galleryImages: List<String>? = null,
    val sku: String? = null,
    val views: Int = 0
) {
    val effectivePrice: Double get() = if (hasDiscount && salePrice != null && salePrice > 0) salePrice else price
    val originalPrice: Double? get() = if (hasDiscount && salePrice != null && salePrice > 0) price else null
    val isInStock: Boolean get() = stockQty > 0
    val images: List<String> get() = if (!galleryImages.isNullOrEmpty()) galleryImages.filter { it.isNotBlank() } else if (imageUrl.isNotBlank()) listOf(imageUrl) else emptyList()
}

data class ProductsData(
    val products: List<Product>,
    val pagination: Pagination
)

data class Pagination(
    val total: Int,
    val page: Int,
    val limit: Int,
    @SerializedName("total_pages") val totalPages: Int
)

data class ProductDetailData(
    val product: Product,
    @SerializedName("related_products") val relatedProducts: List<Product>
)

// === Auth Models ===

data class AuthData(
    val token: String,
    val customer: Customer
)

data class Customer(
    val id: Int,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    val email: String,
    val phone: String? = null,
    @SerializedName("address_line1") val addressLine1: String? = null,
    @SerializedName("address_line2") val addressLine2: String? = null,
    val city: String? = null,
    val state: String? = null,
    val pincode: String? = null
) {
    val fullName: String get() = "$firstName $lastName"
}

// === Order Models ===

data class OrderData(
    @SerializedName("order_number") val orderNumber: String,
    val total: Double,
    val subtotal: Double,
    @SerializedName("shipping_charge") val shippingCharge: Double,
    @SerializedName("payment_method") val paymentMethod: String,
    @SerializedName("upi_id") val upiId: String? = null,
    @SerializedName("upi_name") val upiName: String? = null
)

data class OrderTrackData(
    val order: OrderSummary,
    val items: List<OrderItem>,
    val timeline: List<TimelineStep>
)

data class OrderSummary(
    val id: Int,
    @SerializedName("order_number") val orderNumber: String,
    val status: String,
    @SerializedName("payment_status") val paymentStatus: String,
    @SerializedName("payment_method") val paymentMethod: String?,
    val subtotal: Double,
    @SerializedName("shipping_charge") val shippingCharge: Double,
    val discount: Double,
    val total: Double,
    @SerializedName("shipping_name") val shippingName: String?,
    @SerializedName("shipping_phone") val shippingPhone: String?,
    @SerializedName("shipping_city") val shippingCity: String?,
    @SerializedName("shipping_state") val shippingState: String?,
    @SerializedName("shipping_pincode") val shippingPincode: String?,
    @SerializedName("created_at") val createdAt: String?,
    @SerializedName("updated_at") val updatedAt: String?
)

data class OrderItem(
    @SerializedName("product_name") val productName: String,
    @SerializedName("product_sku") val productSku: String?,
    val quantity: Int,
    val price: Double,
    val total: Double,
    @SerializedName("image_url") val imageUrl: String? = null
)

data class TimelineStep(
    val status: String,
    val label: String,
    val icon: String,
    val completed: Boolean,
    val current: Boolean
)

// === Store Info Models ===

data class StoreInfoData(
    val store: StoreInfo,
    val pages: List<CmsPage>
)

data class StoreInfo(
    val name: String,
    @SerializedName("full_name") val fullName: String,
    val tagline: String,
    val address: String,
    val phone: String,
    val whatsapp: String,
    @SerializedName("whatsapp_url") val whatsappUrl: String,
    val email: String,
    val instagram: String,
    @SerializedName("instagram_url") val instagramUrl: String,
    @SerializedName("hours_morning") val hoursMorning: String,
    @SerializedName("hours_evening") val hoursEvening: String,
    @SerializedName("hours_days") val hoursDays: String,
    @SerializedName("upi_id") val upiId: String?,
    @SerializedName("upi_name") val upiName: String?,
    @SerializedName("currency_symbol") val currencySymbol: String,
    @SerializedName("google_maps_url") val googleMapsUrl: String?
)

data class CmsPage(
    val id: Int,
    val title: String,
    val slug: String,
    val content: String?
)

// === Cart (Local) ===

data class CartItem(
    val product: Product,
    var quantity: Int = 1
) {
    val lineTotal: Double get() = product.effectivePrice * quantity
}

data class OrderRequest(
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    val email: String,
    val phone: String,
    val address: String,
    val city: String,
    val state: String,
    val pincode: String,
    @SerializedName("payment_method") val paymentMethod: String,
    val notes: String = "",
    val items: List<OrderItemRequest>
)

data class OrderItemRequest(
    @SerializedName("product_id") val productId: Int,
    val quantity: Int
)
