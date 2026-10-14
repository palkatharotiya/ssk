package `in`.sskstore.kalakunj.ui.screens

import android.content.Intent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil.compose.AsyncImage
import `in`.sskstore.kalakunj.data.model.Product
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.ErrorView
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.components.ProductCard
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProductDetailScreen(
    viewModel: SSKViewModel,
    productId: Int,
    onNavigateToCart: () -> Unit,
    onNavigateToProduct: (Int) -> Unit,
    onNavigateBack: () -> Unit
) {
    val context = LocalContext.current
    val productDetailState by viewModel.productDetailState.collectAsState()
    val cartCount by viewModel.cartCount.collectAsState()
    val wishlistIds by viewModel.wishlistIds.collectAsState()

    var quantity by remember { mutableIntStateOf(1) }
    var selectedImageIndex by remember { mutableIntStateOf(0) }

    LaunchedEffect(productId) {
        viewModel.loadProductDetail(productId)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Product Details", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color.White)
                    }
                },
                actions = {
                    // Wishlist icon
                    IconButton(onClick = { viewModel.toggleWishlist(productId) }) {
                        Icon(
                            imageVector = if (wishlistIds.contains(productId)) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                            contentDescription = "Wishlist",
                            tint = if (wishlistIds.contains(productId)) ActionCrimson else Color.White
                        )
                    }

                    // Cart with badge
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
                            Icon(Icons.Default.ShoppingCart, contentDescription = "Cart", tint = Color.White)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = SpiritualRoyalBlue)
            )
        },
        bottomBar = {
            val state = productDetailState
            if (state is Result.Success) {
                val product = state.data.product
                Surface(
                    tonalElevation = 8.dp,
                    shadowElevation = 8.dp,
                    color = MaterialTheme.colorScheme.surface
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 10.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        // Quantity Stepper
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .background(MaterialTheme.colorScheme.surfaceVariant)
                                .padding(horizontal = 4.dp, vertical = 2.dp)
                        ) {
                            IconButton(
                                onClick = { if (quantity > 1) quantity-- },
                                modifier = Modifier.size(32.dp)
                            ) {
                                Text("-", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                            }
                            Text(
                                text = quantity.toString(),
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(horizontal = 8.dp)
                            )
                            IconButton(
                                onClick = { if (quantity < product.stockQty && quantity < 20) quantity++ },
                                modifier = Modifier.size(32.dp)
                            ) {
                                Text("+", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                            }
                        }

                        // Add to Cart Button
                        Button(
                            onClick = {
                                viewModel.addToCart(product, quantity)
                            },
                            enabled = product.isInStock,
                            shape = RoundedCornerShape(8.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = SpiritualRoyalBlue,
                                contentColor = Color.White
                            ),
                            modifier = Modifier
                                .weight(1f)
                                .height(46.dp)
                        ) {
                            Icon(Icons.Default.ShoppingCart, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = if (product.isInStock) "Add to Cart (₹${(product.price * quantity).toInt()})" else "Out of Stock",
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp
                            )
                        }
                    }
                }
            }
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            when (val state = productDetailState) {
                null, is Result.Loading -> LoadingView()
                is Result.Error -> ErrorView(
                    message = state.message,
                    onRetry = { viewModel.loadProductDetail(productId) }
                )
                is Result.Success -> {
                    val detail = state.data
                    val product = detail.product
                    val images = if (product.images.isNotEmpty()) product.images else listOf(product.imageUrl)

                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                    ) {
                        // Product Main Image
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(320.dp)
                                .background(Color(0xFFF7F7F7))
                        ) {
                            AsyncImage(
                                model = images.getOrElse(selectedImageIndex) { product.imageUrl },
                                contentDescription = product.name,
                                contentScale = ContentScale.Fit,
                                modifier = Modifier.fillMaxSize()
                            )

                            // Discount pill
                            if (product.discountPercent > 0) {
                                Box(
                                    modifier = Modifier
                                        .padding(14.dp)
                                        .align(Alignment.TopStart)
                                        .clip(RoundedCornerShape(6.dp))
                                        .background(ActionCrimson)
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text(
                                        text = "${product.discountPercent}% SAVINGS",
                                        color = Color.White,
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Bold
                                    )
                                }
                            }

                            // Share button
                            IconButton(
                                onClick = {
                                    val shareIntent = Intent().apply {
                                        action = Intent.ACTION_SEND
                                        putExtra(Intent.EXTRA_TEXT, "${product.name} - SSK Store Kalakunj Mandir: ₹${product.price.toInt()}")
                                        type = "text/plain"
                                    }
                                    context.startActivity(Intent.createChooser(shareIntent, "Share sacred publication"))
                                },
                                modifier = Modifier
                                    .padding(14.dp)
                                    .align(Alignment.TopEnd)
                                    .background(Color.White.copy(alpha = 0.9f), CircleShape)
                            ) {
                                Icon(Icons.Default.Share, contentDescription = "Share", tint = SpiritualRoyalBlue)
                            }
                        }

                        // Thumbnail Gallery
                        if (images.size > 1) {
                            LazyRow(
                                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(images.indices.toList()) { index ->
                                    Box(
                                        modifier = Modifier
                                            .size(56.dp)
                                            .clip(RoundedCornerShape(6.dp))
                                            .background(Color(0xFFEEEEEE))
                                            .clickable { selectedImageIndex = index }
                                    ) {
                                        AsyncImage(
                                            model = images[index],
                                            contentDescription = null,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                }
                            }
                        }

                        // Product Title & Meta
                        Column(modifier = Modifier.padding(16.dp)) {
                            // Department & SKU
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = product.departmentName ?: "Publications",
                                    color = SpiritualRoyalBlue,
                                    fontWeight = FontWeight.SemiBold,
                                    fontSize = 13.sp
                                )
                                Text(
                                    text = "SKU: ${product.sku}",
                                    color = Color.Gray,
                                    fontSize = 12.sp
                                )
                            }

                            Spacer(modifier = Modifier.height(4.dp))

                            Text(
                                text = product.name,
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onBackground
                            )

                            Spacer(modifier = Modifier.height(10.dp))

                            // Pricing Row
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(
                                    text = "₹${product.price.toInt()}",
                                    fontSize = 24.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = SpiritualRoyalBlue
                                )
                                val origPrice = product.originalPrice
                                if (origPrice != null && origPrice > product.price) {
                                    Spacer(modifier = Modifier.width(10.dp))
                                    Text(
                                        text = "₹${origPrice.toInt()}",
                                        fontSize = 15.sp,
                                        color = Color.Gray,
                                        textDecoration = TextDecoration.LineThrough
                                    )
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text(
                                        text = "(${product.discountPercent}% OFF)",
                                        fontSize = 13.sp,
                                        color = ActionCrimson,
                                        fontWeight = FontWeight.Bold
                                    )
                                }
                            }

                            // Stock status badge
                            Spacer(modifier = Modifier.height(8.dp))
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(8.dp)
                                        .clip(CircleShape)
                                        .background(if (product.isInStock) Color(0xFF16A34A) else ActionCrimson)
                                )
                                Spacer(modifier = Modifier.width(6.dp))
                                Text(
                                    text = if (product.isInStock) "In Stock (${product.stockQty} available)" else "Temporarily Out of Stock",
                                    fontSize = 12.sp,
                                    color = if (product.isInStock) Color(0xFF16A34A) else ActionCrimson,
                                    fontWeight = FontWeight.Medium
                                )
                            }

                            Divider(modifier = Modifier.padding(vertical = 14.dp))

                            // Sacred Mandir Guarantee Box
                            Card(
                                shape = RoundedCornerShape(8.dp),
                                colors = CardDefaults.cardColors(containerColor = SacredGoldLight.copy(alpha = 0.25f)),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Row(
                                    modifier = Modifier.padding(12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.Verified,
                                        contentDescription = null,
                                        tint = SpiritualRoyalBlue,
                                        modifier = Modifier.size(24.dp)
                                    )
                                    Spacer(modifier = Modifier.width(10.dp))
                                    Column {
                                        Text(
                                            text = "100% Authentic Mandir Publication",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 12.sp,
                                            color = SpiritualRoyalBlue
                                        )
                                        Text(
                                            text = "Dispatched directly from Shri Sahajanand Sahitya Kendra, Surat",
                                            fontSize = 11.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(16.dp))

                            // Description
                            Text(
                                text = "Description & Spiritual Value",
                                fontWeight = FontWeight.Bold,
                                fontSize = 15.sp,
                                color = MaterialTheme.colorScheme.onBackground
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = product.description?.ifBlank { "Sacred publication from Shri Sahajanand Sahitya Kendra, Kalakunj Mandir, Surat. Ideal for daily satsang, spiritual reflection, and mandir worship." } ?: "Sacred publication from Shri Sahajanand Sahitya Kendra, Kalakunj Mandir, Surat.",
                                fontSize = 13.sp,
                                lineHeight = 20.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )

                            // Related Products
                            if (detail.relatedProducts.isNotEmpty()) {
                                Spacer(modifier = Modifier.height(24.dp))
                                Text(
                                    text = "Related Publications & Items",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = MaterialTheme.colorScheme.onBackground
                                )
                                Spacer(modifier = Modifier.height(8.dp))
                                LazyRow(
                                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    items(detail.relatedProducts) { related ->
                                        ProductCard(
                                            product = related,
                                            onProductClick = onNavigateToProduct,
                                            onAddToCart = { viewModel.addToCart(it) },
                                            isWishlisted = wishlistIds.contains(related.id),
                                            onToggleWishlist = { viewModel.toggleWishlist(it) },
                                            modifier = Modifier.width(160.dp)
                                        )
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(24.dp))
                        }
                    }
                }
            }
        }
    }
}
