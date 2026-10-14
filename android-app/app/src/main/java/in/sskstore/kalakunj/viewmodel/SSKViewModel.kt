package `in`.sskstore.kalakunj.viewmodel

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import `in`.sskstore.kalakunj.data.local.SSKPreferences
import `in`.sskstore.kalakunj.data.model.*
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.data.repository.SSKRepository
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

class SSKViewModel(application: Application) : AndroidViewModel(application) {

    private val repository = SSKRepository()
    val prefs = SSKPreferences(application)

    // === UI State ===
    private val _homeState = MutableStateFlow<Result<HomeData>>(Result.Loading)
    val homeState: StateFlow<Result<HomeData>> = _homeState.asStateFlow()

    private val _productsState = MutableStateFlow<Result<ProductsData>>(Result.Loading)
    val productsState: StateFlow<Result<ProductsData>> = _productsState.asStateFlow()

    private val _productDetailState = MutableStateFlow<Result<ProductDetailData>?>(null)
    val productDetailState: StateFlow<Result<ProductDetailData>?> = _productDetailState.asStateFlow()

    private val _authState = MutableStateFlow<Result<AuthData>?>(null)
    val authState: StateFlow<Result<AuthData>?> = _authState.asStateFlow()

    private val _orderState = MutableStateFlow<Result<OrderData>?>(null)
    val orderState: StateFlow<Result<OrderData>?> = _orderState.asStateFlow()

    private val _trackingState = MutableStateFlow<Result<OrderTrackData>?>(null)
    val trackingState: StateFlow<Result<OrderTrackData>?> = _trackingState.asStateFlow()

    private val _storeInfoState = MutableStateFlow<Result<StoreInfoData>?>(null)
    val storeInfoState: StateFlow<Result<StoreInfoData>?> = _storeInfoState.asStateFlow()

    private val _snackbarMessage = MutableSharedFlow<String>()
    val snackbarMessage: SharedFlow<String> = _snackbarMessage.asSharedFlow()

    // Cart state — in-memory + persisted
    private val _cartItems = MutableStateFlow<List<CartItem>>(emptyList())
    val cartItems: StateFlow<List<CartItem>> = _cartItems.asStateFlow()

    val cartCount: StateFlow<Int> = _cartItems.map { items ->
        items.sumOf { it.quantity }
    }.stateIn(viewModelScope, SharingStarted.Lazily, 0)

    val cartSubtotal: StateFlow<Double> = _cartItems.map { items ->
        items.sumOf { it.lineTotal }
    }.stateIn(viewModelScope, SharingStarted.Lazily, 0.0)

    // Wishlist state
    private val _wishlistIds = MutableStateFlow<Set<Int>>(emptySet())
    val wishlistIds: StateFlow<Set<Int>> = _wishlistIds.asStateFlow()

    // Theme
    val isDarkMode: StateFlow<Boolean> = prefs.isDarkMode
        .stateIn(viewModelScope, SharingStarted.Eagerly, false)

    // Auth
    val customer: StateFlow<Customer?> = prefs.customer
        .stateIn(viewModelScope, SharingStarted.Eagerly, null)

    val isLoggedIn: StateFlow<Boolean> = prefs.authToken
        .map { !it.isNullOrBlank() }
        .stateIn(viewModelScope, SharingStarted.Eagerly, false)

    // Search
    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    init {
        // Load persisted cart and wishlist
        viewModelScope.launch {
            prefs.cartItems.collect { _cartItems.value = it }
        }
        viewModelScope.launch {
            prefs.wishlistIds.collect { _wishlistIds.value = it }
        }
        // Auto-load home data
        loadHomeData()
    }

    // === Home ===
    fun loadHomeData() {
        viewModelScope.launch {
            _homeState.value = Result.Loading
            _homeState.value = repository.getHomeData()
        }
    }

    // === Products ===
    fun loadProducts(
        dept: Int? = null,
        category: Int? = null,
        search: String? = null,
        onSale: Boolean = false,
        sort: String? = null,
        page: Int = 1
    ) {
        viewModelScope.launch {
            _productsState.value = Result.Loading
            _productsState.value = repository.getProducts(dept, category, search, onSale, sort, page)
        }
    }

    fun updateSearchQuery(query: String) {
        _searchQuery.value = query
    }

    // === Product Detail ===
    fun loadProductDetail(productId: Int) {
        viewModelScope.launch {
            _productDetailState.value = Result.Loading
            _productDetailState.value = repository.getProductDetail(productId)
        }
    }

    fun clearProductDetail() {
        _productDetailState.value = null
    }

    // === Auth ===
    fun login(email: String, password: String) {
        viewModelScope.launch {
            _authState.value = Result.Loading
            val result = repository.login(email, password)
            _authState.value = result
            if (result is Result.Success) {
                prefs.saveAuth(result.data.token, result.data.customer)
                showSnackbar("Welcome back, ${result.data.customer.firstName}! 🙏")
            }
        }
    }

    fun register(firstName: String, lastName: String, email: String, password: String, phone: String) {
        viewModelScope.launch {
            _authState.value = Result.Loading
            val result = repository.register(firstName, lastName, email, password, phone)
            _authState.value = result
            if (result is Result.Success) {
                prefs.saveAuth(result.data.token, result.data.customer)
                showSnackbar("Account created! Welcome, ${result.data.customer.firstName}! 🙏")
            }
        }
    }

    fun forgotPassword(email: String) {
        viewModelScope.launch {
            val result = repository.forgotPassword(email)
            when (result) {
                is Result.Success -> showSnackbar(result.data)
                is Result.Error -> showSnackbar(result.message)
                else -> {}
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            prefs.clearAuth()
            _authState.value = null
            showSnackbar("Logged out successfully.")
        }
    }

    fun clearAuthState() {
        _authState.value = null
    }

    // === Cart ===
    fun addToCart(product: Product, quantity: Int = 1) {
        val current = _cartItems.value.toMutableList()
        val existingIndex = current.indexOfFirst { it.product.id == product.id }

        if (existingIndex >= 0) {
            val existing = current[existingIndex]
            val newQty = minOf(existing.quantity + quantity, product.stockQty, 20)
            if (newQty == existing.quantity) {
                viewModelScope.launch { showSnackbar("Maximum quantity reached for \"${product.name}\"") }
                return
            }
            current[existingIndex] = existing.copy(quantity = newQty)
        } else {
            current.add(CartItem(product, minOf(quantity, product.stockQty, 20)))
        }

        _cartItems.value = current
        persistCart()
        viewModelScope.launch { showSnackbar("\"${product.name}\" added to cart ✓") }
    }

    fun updateCartQuantity(productId: Int, quantity: Int) {
        if (quantity <= 0) {
            removeFromCart(productId)
            return
        }
        val current = _cartItems.value.toMutableList()
        val index = current.indexOfFirst { it.product.id == productId }
        if (index >= 0) {
            val item = current[index]
            current[index] = item.copy(quantity = minOf(quantity, item.product.stockQty, 20))
            _cartItems.value = current
            persistCart()
        }
    }

    fun removeFromCart(productId: Int) {
        val current = _cartItems.value.toMutableList()
        current.removeAll { it.product.id == productId }
        _cartItems.value = current
        persistCart()
        viewModelScope.launch { showSnackbar("Item removed from cart.") }
    }

    fun clearCart() {
        _cartItems.value = emptyList()
        persistCart()
    }

    private fun persistCart() {
        viewModelScope.launch { prefs.saveCart(_cartItems.value) }
    }

    // === Wishlist ===
    fun toggleWishlist(productId: Int) {
        val current = _wishlistIds.value.toMutableSet()
        if (current.contains(productId)) {
            current.remove(productId)
            viewModelScope.launch { showSnackbar("Removed from wishlist") }
        } else {
            current.add(productId)
            viewModelScope.launch { showSnackbar("Added to wishlist ❤") }
        }
        _wishlistIds.value = current
        viewModelScope.launch { prefs.saveWishlist(current) }
    }

    fun isInWishlist(productId: Int): Boolean = _wishlistIds.value.contains(productId)

    // === Orders ===
    fun createOrder(request: OrderRequest) {
        viewModelScope.launch {
            _orderState.value = Result.Loading
            val result = repository.createOrder(request)
            _orderState.value = result
            if (result is Result.Success) {
                clearCart()
            }
        }
    }

    fun trackOrder(orderNumber: String) {
        viewModelScope.launch {
            _trackingState.value = Result.Loading
            _trackingState.value = repository.trackOrder(orderNumber)
        }
    }

    fun clearOrderState() {
        _orderState.value = null
    }

    fun clearTrackingState() {
        _trackingState.value = null
    }

    // === Store Info ===
    fun loadStoreInfo() {
        viewModelScope.launch {
            _storeInfoState.value = Result.Loading
            _storeInfoState.value = repository.getStoreInfo()
        }
    }

    // === Theme ===
    fun toggleDarkMode() {
        viewModelScope.launch {
            val current = isDarkMode.value
            prefs.setDarkMode(!current)
        }
    }

    // === Snackbar ===
    private suspend fun showSnackbar(message: String) {
        _snackbarMessage.emit(message)
    }
}
