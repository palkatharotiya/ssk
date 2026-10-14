package `in`.sskstore.kalakunj.navigation

sealed class Screen(val route: String) {
    data object Splash : Screen("splash")
    data object Home : Screen("home")
    data object Catalog : Screen("catalog?dept={dept}&search={search}&onSale={onSale}") {
        fun createRoute(dept: Int? = null, search: String? = null, onSale: Boolean = false): String {
            return "catalog?dept=${dept ?: ""}&search=${search ?: ""}&onSale=$onSale"
        }
    }
    data object ProductDetail : Screen("product/{productId}") {
        fun createRoute(productId: Int) = "product/$productId"
    }
    data object Cart : Screen("cart")
    data object Checkout : Screen("checkout")
    data object OrderSuccess : Screen("order_success/{orderNumber}") {
        fun createRoute(orderNumber: String) = "order_success/$orderNumber"
    }
    data object TrackOrder : Screen("track_order")
    data object Auth : Screen("auth")
    data object StoreInfo : Screen("store_info")
    data object PageDetail : Screen("page/{slug}") {
        fun createRoute(slug: String) = "page/$slug"
    }
}
