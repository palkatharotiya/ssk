package `in`.sskstore.kalakunj.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import `in`.sskstore.kalakunj.data.model.Product
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.ErrorView
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.components.ProductCard
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CatalogScreen(
    viewModel: SSKViewModel,
    initialDept: Int? = null,
    initialSearch: String? = null,
    initialOnSale: Boolean = false,
    onNavigateToProduct: (Int) -> Unit,
    onNavigateToCart: () -> Unit,
    onNavigateBack: () -> Unit
) {
    var selectedDept by remember { mutableStateOf(initialDept) }
    var searchQuery by remember { mutableStateOf(initialSearch ?: "") }
    var onlyOnSale by remember { mutableStateOf(initialOnSale) }
    var selectedSort by remember { mutableStateOf("popular") }
    var showSortMenu by remember { mutableStateOf(false) }

    val productsState by viewModel.productsState.collectAsState()
    val cartCount by viewModel.cartCount.collectAsState()
    val wishlistIds by viewModel.wishlistIds.collectAsState()

    // Trigger load when filters change
    LaunchedEffect(selectedDept, searchQuery, onlyOnSale, selectedSort) {
        viewModel.loadProducts(
            dept = selectedDept,
            search = searchQuery.takeIf { it.isNotBlank() },
            onSale = onlyOnSale,
            sort = selectedSort,
            page = 1
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(
                            text = if (onlyOnSale) "Spiritual Sale"
                            else if (!searchQuery.isBlank()) "Search: $searchQuery"
                            else "Sacred Catalog",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Text(
                            text = "Shri Sahajanand Sahitya Kendra",
                            fontSize = 11.sp,
                            color = SacredGoldLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.Default.ArrowBack,
                            contentDescription = "Back",
                            tint = Color.White
                        )
                    }
                },
                actions = {
                    // Sort dropdown
                    Box {
                        IconButton(onClick = { showSortMenu = true }) {
                            Icon(
                                imageVector = Icons.Default.Sort,
                                contentDescription = "Sort",
                                tint = Color.White
                            )
                        }
                        DropdownMenu(
                            expanded = showSortMenu,
                            onDismissRequest = { showSortMenu = false }
                        ) {
                            DropdownMenuItem(
                                text = { Text("Most Popular") },
                                onClick = { selectedSort = "popular"; showSortMenu = false }
                            )
                            DropdownMenuItem(
                                text = { Text("Price: Low to High") },
                                onClick = { selectedSort = "price_asc"; showSortMenu = false }
                            )
                            DropdownMenuItem(
                                text = { Text("Price: High to Low") },
                                onClick = { selectedSort = "price_desc"; showSortMenu = false }
                            )
                            DropdownMenuItem(
                                text = { Text("Name: A to Z") },
                                onClick = { selectedSort = "name_asc"; showSortMenu = false }
                            )
                        }
                    }

                    // Cart
                    BadgedBox(
                        badge = {
                            if (cartCount > 0) {
                                Badge(containerColor = ActionCrimson) {
                                    Text(text = cartCount.toString(), color = Color.White)
                                }
                            }
                        }
                    ) {
                        IconButton(onClick = onNavigateToCart) {
                            Icon(
                                imageVector = Icons.Default.ShoppingCart,
                                contentDescription = "Cart",
                                tint = Color.White
                            )
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = SpiritualRoyalBlue
                )
            )
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // Search Input inside Catalog
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.surface,
                shadowElevation = 2.dp
            ) {
                Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        placeholder = { Text("Filter items in catalog...", fontSize = 13.sp) },
                        leadingIcon = {
                            Icon(Icons.Default.Search, contentDescription = null, tint = SpiritualRoyalBlue)
                        },
                        trailingIcon = {
                            if (searchQuery.isNotBlank()) {
                                IconButton(onClick = { searchQuery = "" }) {
                                    Icon(Icons.Default.Close, contentDescription = "Clear")
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(20.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(50.dp)
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    // Filter Chips Row
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        FilterChip(
                            selected = selectedDept == null && !onlyOnSale,
                            onClick = {
                                selectedDept = null
                                onlyOnSale = false
                            },
                            label = { Text("All", fontSize = 12.sp) }
                        )

                        FilterChip(
                            selected = onlyOnSale,
                            onClick = { onlyOnSale = !onlyOnSale },
                            label = { Text("⚡ On Sale", fontSize = 12.sp) }
                        )

                        // Department chips (1: Books, 2: Murti, 3: Puja, 4: Audio, 5: Garments, 6: Gifts)
                        val depts = listOf(
                            1 to "Sacred Books",
                            2 to "Murti & Vigrah",
                            3 to "Puja Samagri",
                            4 to "Audio & Kirtan",
                            5 to "Vastra / Garments",
                            6 to "Gifts & Novelties"
                        )

                        depts.forEach { (id, name) ->
                            FilterChip(
                                selected = selectedDept == id,
                                onClick = {
                                    selectedDept = if (selectedDept == id) null else id
                                },
                                label = { Text(name, fontSize = 12.sp) }
                            )
                        }
                    }
                }
            }

            // Products Grid
            Box(modifier = Modifier.fillMaxSize()) {
                when (val state = productsState) {
                    is Result.Loading -> LoadingView()
                    is Result.Error -> ErrorView(
                        message = state.message,
                        onRetry = {
                            viewModel.loadProducts(selectedDept, null, searchQuery, onlyOnSale, selectedSort, 1)
                        }
                    )
                    is Result.Success -> {
                        val products = state.data.products
                        if (products.isEmpty()) {
                            Column(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .padding(32.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.SearchOff,
                                    contentDescription = null,
                                    tint = Color.Gray,
                                    modifier = Modifier.size(56.dp)
                                )
                                Spacer(modifier = Modifier.height(12.dp))
                                Text(
                                    text = "No sacred items match your filter",
                                    fontSize = 16.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(
                                    text = "Try clearing search keywords or selecting All departments.",
                                    fontSize = 13.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    textAlign = TextAlign.Center
                                )
                                Spacer(modifier = Modifier.height(16.dp))
                                Button(
                                    onClick = {
                                        selectedDept = null
                                        searchQuery = ""
                                        onlyOnSale = false
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = SpiritualRoyalBlue)
                                ) {
                                    Text("Reset Filters")
                                }
                            }
                        } else {
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                contentPadding = PaddingValues(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(10.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                items(products) { product ->
                                    ProductCard(
                                        product = product,
                                        onProductClick = onNavigateToProduct,
                                        onAddToCart = { viewModel.addToCart(it) },
                                        isWishlisted = wishlistIds.contains(product.id),
                                        onToggleWishlist = { viewModel.toggleWishlist(it) }
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
