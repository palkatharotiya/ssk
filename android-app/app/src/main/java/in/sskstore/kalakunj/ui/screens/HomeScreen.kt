package `in`.sskstore.kalakunj.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil.compose.AsyncImage
import `in`.sskstore.kalakunj.data.model.Department
import `in`.sskstore.kalakunj.data.model.Product
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.ErrorView
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.components.ProductCard
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    viewModel: SSKViewModel,
    onNavigateToCatalog: (dept: Int?, search: String?, onSale: Boolean) -> Unit,
    onNavigateToProduct: (Int) -> Unit,
    onNavigateToCart: () -> Unit,
    onNavigateToAuth: () -> Unit,
    onNavigateToTrackOrder: () -> Unit,
    onNavigateToStoreInfo: () -> Unit
) {
    val homeState by viewModel.homeState.collectAsState()
    val cartCount by viewModel.cartCount.collectAsState()
    val wishlistIds by viewModel.wishlistIds.collectAsState()
    val isDarkMode by viewModel.isDarkMode.collectAsState()
    val isLoggedIn by viewModel.isLoggedIn.collectAsState()
    val customer by viewModel.customer.collectAsState()

    var searchText by remember { mutableStateOf("") }

    Scaffold(
        topBar = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(
                        Brush.horizontalGradient(
                            colors = listOf(SpiritualRoyalBlue, Color(0xFF1E3A8A))
                        )
                    )
                    .padding(horizontal = 16.dp, vertical = 10.dp)
            ) {
                // Header Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                text = "ૐ",
                                fontSize = 22.sp,
                                fontWeight = FontWeight.Bold,
                                color = SacredGold
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "SSK Store",
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                        Text(
                            text = "Kalakunj Mandir, Surat",
                            fontSize = 11.sp,
                            color = SacredGoldLight
                        )
                    }

                    // Actions
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        // Dark mode toggle
                        IconButton(onClick = { viewModel.toggleDarkMode() }) {
                            Icon(
                                imageVector = if (isDarkMode) Icons.Default.Brightness7 else Icons.Default.Brightness4,
                                contentDescription = "Toggle Theme",
                                tint = Color.White
                            )
                        }

                        // Auth / Account button
                        IconButton(onClick = onNavigateToAuth) {
                            Icon(
                                imageVector = if (isLoggedIn) Icons.Default.AccountCircle else Icons.Default.PersonOutline,
                                contentDescription = "Account",
                                tint = if (isLoggedIn) SacredGold else Color.White
                            )
                        }

                        // Cart badge
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
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                // Search Bar
                OutlinedTextField(
                    value = searchText,
                    onValueChange = { searchText = it },
                    placeholder = {
                        Text(
                            "Search sacred books, puja items, murti...",
                            fontSize = 13.sp,
                            color = Color.Gray
                        )
                    },
                    leadingIcon = {
                        Icon(
                            imageVector = Icons.Default.Search,
                            contentDescription = "Search",
                            tint = SpiritualRoyalBlue
                        )
                    },
                    trailingIcon = {
                        if (searchText.isNotBlank()) {
                            IconButton(onClick = {
                                onNavigateToCatalog(null, searchText.trim(), false)
                            }) {
                                Icon(
                                    imageVector = Icons.Default.ArrowForward,
                                    contentDescription = "Go",
                                    tint = SacredGoldDark
                                )
                            }
                        }
                    },
                    singleLine = true,
                    shape = RoundedCornerShape(24.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedContainerColor = Color.White,
                        unfocusedContainerColor = Color.White,
                        focusedBorderColor = SacredGold,
                        unfocusedBorderColor = Color.Transparent,
                        focusedTextColor = Color.Black,
                        unfocusedTextColor = Color.Black
                    ),
                    modifier = Modifier.fillMaxWidth()
                )
            }
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            when (val state = homeState) {
                is Result.Loading -> LoadingView()
                is Result.Error -> ErrorView(
                    message = state.message,
                    onRetry = { viewModel.loadHomeData() }
                )
                is Result.Success -> {
                    val data = state.data
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                    ) {
                        // Greeting if logged in
                        if (isLoggedIn && customer != null) {
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp, vertical = 8.dp),
                                colors = CardDefaults.cardColors(containerColor = SacredGoldLight.copy(alpha = 0.2f)),
                                shape = RoundedCornerShape(10.dp)
                            ) {
                                Row(
                                    modifier = Modifier.padding(12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = "Jay Swaminarayan, ${customer?.firstName}! 🙏",
                                        fontWeight = FontWeight.SemiBold,
                                        fontSize = 13.sp,
                                        color = SpiritualRoyalBlue
                                    )
                                }
                            }
                        }

                        // Banners Carousel
                        if (data.banners.isNotEmpty()) {
                            LazyRow(
                                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 10.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(data.banners) { banner ->
                                    Card(
                                        shape = RoundedCornerShape(12.dp),
                                        elevation = CardDefaults.cardElevation(defaultElevation = 3.dp),
                                        modifier = Modifier
                                            .width(320.dp)
                                            .height(140.dp)
                                            .clickable {
                                                onNavigateToCatalog(null, null, true)
                                            }
                                    ) {
                                        Box(modifier = Modifier.fillMaxSize()) {
                                            AsyncImage(
                                                model = banner.imageUrl,
                                                contentDescription = banner.title,
                                                contentScale = ContentScale.Crop,
                                                modifier = Modifier.fillMaxSize()
                                            )
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxSize()
                                                    .background(
                                                        Brush.verticalGradient(
                                                            colors = listOf(Color.Transparent, Color.Black.copy(alpha = 0.7f))
                                                        )
                                                    )
                                            )
                                            Column(
                                                modifier = Modifier
                                                    .align(Alignment.BottomStart)
                                                    .padding(12.dp)
                                            ) {
                                                Text(
                                                    text = banner.title ?: "SSK Store",
                                                    color = Color.White,
                                                    fontWeight = FontWeight.Bold,
                                                    fontSize = 15.sp
                                                )
                                                if (!banner.subtitle.isNullOrBlank()) {
                                                    Text(
                                                        text = banner.subtitle,
                                                        color = SacredGoldLight,
                                                        fontSize = 12.sp
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Sacred Departments
                        SectionHeader(
                            title = "Sacred Departments",
                            subtitle = "Explore categories of spiritual publications & items",
                            onViewAll = { onNavigateToCatalog(null, null, false) }
                        )

                        LazyRow(
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(data.departments) { dept ->
                                DepartmentItem(
                                    dept = dept,
                                    onClick = { onNavigateToCatalog(dept.id, null, false) }
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Featured Products
                        if (data.featuredProducts.isNotEmpty()) {
                            SectionHeader(
                                title = "Featured Publications & Murti",
                                subtitle = "Handpicked sacred items for your daily satsang",
                                onViewAll = { onNavigateToCatalog(null, null, false) }
                            )

                            LazyRow(
                                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(data.featuredProducts) { product ->
                                    ProductCard(
                                        product = product,
                                        onProductClick = onNavigateToProduct,
                                        onAddToCart = { viewModel.addToCart(it) },
                                        isWishlisted = wishlistIds.contains(product.id),
                                        onToggleWishlist = { viewModel.toggleWishlist(it) },
                                        modifier = Modifier.width(170.dp)
                                    )
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Special Sale Banner
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp)
                                .clickable { onNavigateToCatalog(null, null, true) },
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = ActionCrimson)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Column {
                                    Text(
                                        text = "Festive Spiritual Sale",
                                        color = Color.White,
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp
                                    )
                                    Text(
                                        text = "Up to 30% discount on sacred books & sets",
                                        color = Color.White.copy(alpha = 0.85f),
                                        fontSize = 12.sp
                                    )
                                }
                                Button(
                                    onClick = { onNavigateToCatalog(null, null, true) },
                                    colors = ButtonDefaults.buttonColors(
                                        containerColor = SacredGold,
                                        contentColor = SpiritualRoyalBlue
                                    ),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Text("Explore", fontWeight = FontWeight.Bold, fontSize = 12.sp)
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // New Arrivals
                        if (data.newArrivals.isNotEmpty()) {
                            SectionHeader(
                                title = "New Arrivals",
                                subtitle = "Latest publications, audio collections & articles",
                                onViewAll = { onNavigateToCatalog(null, null, false) }
                            )

                            LazyRow(
                                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(data.newArrivals) { product ->
                                    ProductCard(
                                        product = product,
                                        onProductClick = onNavigateToProduct,
                                        onAddToCart = { viewModel.addToCart(it) },
                                        isWishlisted = wishlistIds.contains(product.id),
                                        onToggleWishlist = { viewModel.toggleWishlist(it) },
                                        modifier = Modifier.width(170.dp)
                                    )
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(18.dp))

                        // Quick Service Actions
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text(
                                    text = "Mandir Services & Help",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Spacer(modifier = Modifier.height(12.dp))

                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceAround
                                ) {
                                    QuickActionItem(
                                        icon = Icons.Default.LocalShipping,
                                        label = "Track Order",
                                        onClick = onNavigateToTrackOrder
                                    )
                                    QuickActionItem(
                                        icon = Icons.Default.Place,
                                        label = "Mandir Info",
                                        onClick = onNavigateToStoreInfo
                                    )
                                    QuickActionItem(
                                        icon = Icons.Default.Policy,
                                        label = "Policies",
                                        onClick = onNavigateToStoreInfo
                                    )
                                    QuickActionItem(
                                        icon = Icons.Default.Phone,
                                        label = "Contact",
                                        onClick = onNavigateToStoreInfo
                                    )
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(30.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun SectionHeader(
    title: String,
    subtitle: String,
    onViewAll: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                fontSize = 16.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = subtitle,
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
        }
        TextButton(onClick = onViewAll) {
            Text(
                text = "View All",
                color = SpiritualRoyalBlue,
                fontWeight = FontWeight.SemiBold,
                fontSize = 12.sp
            )
        }
    }
}

@Composable
private fun DepartmentItem(
    dept: Department,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .width(82.dp)
            .clickable { onClick() }
    ) {
        Box(
            modifier = Modifier
                .size(64.dp)
                .clip(CircleShape)
                .background(SpiritualRoyalBlue.copy(alpha = 0.1f)),
            contentAlignment = Alignment.Center
        ) {
            if (!dept.imageUrl.isNullOrBlank()) {
                AsyncImage(
                    model = dept.imageUrl,
                    contentDescription = dept.name,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize()
                )
            } else {
                Icon(
                    imageVector = Icons.Default.AutoStories,
                    contentDescription = dept.name,
                    tint = SpiritualRoyalBlue,
                    modifier = Modifier.size(28.dp)
                )
            }
        }
        Spacer(modifier = Modifier.height(6.dp))
        Text(
            text = dept.name,
            fontSize = 11.sp,
            fontWeight = FontWeight.Medium,
            textAlign = TextAlign.Center,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis,
            color = MaterialTheme.colorScheme.onBackground
        )
    }
}

@Composable
private fun QuickActionItem(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier.clickable { onClick() }
    ) {
        Box(
            modifier = Modifier
                .size(44.dp)
                .clip(CircleShape)
                .background(SpiritualRoyalBlue.copy(alpha = 0.12f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = icon,
                contentDescription = label,
                tint = SpiritualRoyalBlue,
                modifier = Modifier.size(22.dp)
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = label,
            fontSize = 11.sp,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}
