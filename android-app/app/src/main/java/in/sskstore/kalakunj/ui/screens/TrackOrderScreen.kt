package `in`.sskstore.kalakunj.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import `in`.sskstore.kalakunj.data.model.OrderTrackData
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TrackOrderScreen(
    viewModel: SSKViewModel,
    onNavigateBack: () -> Unit
) {
    var orderNumberInput by remember { mutableStateOf("") }
    val trackingState by viewModel.trackingState.collectAsState()

    DisposableEffect(Unit) {
        onDispose {
            viewModel.clearTrackingState()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Track Sacred Order", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color.White)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = SpiritualRoyalBlue)
            )
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Search Input Card
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = "Enter Your Order Tracking ID",
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = SpiritualRoyalBlue
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        text = "You can find your order number in your confirmation SMS/WhatsApp (e.g., SSK-ORD-XXXXXX)",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Spacer(modifier = Modifier.height(12.dp))

                    OutlinedTextField(
                        value = orderNumberInput,
                        onValueChange = { orderNumberInput = it },
                        placeholder = { Text("e.g., SSK-ORD-123456", fontSize = 13.sp) },
                        leadingIcon = {
                            Icon(Icons.Default.Search, contentDescription = null, tint = SpiritualRoyalBlue)
                        },
                        trailingIcon = {
                            if (orderNumberInput.isNotBlank()) {
                                IconButton(onClick = { orderNumberInput = "" }) {
                                    Icon(Icons.Default.Close, contentDescription = "Clear")
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    Button(
                        onClick = {
                            if (orderNumberInput.isNotBlank()) {
                                viewModel.trackOrder(orderNumberInput.trim())
                            }
                        },
                        enabled = orderNumberInput.isNotBlank() && trackingState !is Result.Loading,
                        shape = RoundedCornerShape(8.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = SpiritualRoyalBlue,
                            contentColor = Color.White
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(44.dp)
                    ) {
                        if (trackingState is Result.Loading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Tracking...")
                        } else {
                            Icon(Icons.Default.LocalShipping, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Track Order Status", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }

            // Results Section
            when (val state = trackingState) {
                null -> {}
                is Result.Loading -> LoadingView(modifier = Modifier.height(200.dp))
                is Result.Error -> {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFFEE2E2)),
                        shape = RoundedCornerShape(10.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.ErrorOutline, contentDescription = null, tint = ActionCrimson)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Order Not Found", fontWeight = FontWeight.Bold, color = ActionCrimson)
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = state.message,
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
                is Result.Success -> {
                    TrackingDetailsView(data = state.data)
                }
            }
        }
    }
}

@Composable
private fun TrackingDetailsView(data: OrderTrackData) {
    val order = data.order
    Column(verticalArrangement = Arrangement.spacedBy(14.dp)) {
        // Status Overview Card
        Card(
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column {
                        Text("Order #${order.orderNumber}", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = SpiritualRoyalBlue)
                        Text("Placed on ${order.createdAt ?: "Recently"}", fontSize = 12.sp, color = Color.Gray)
                    }

                    // Status Badge
                    val statusColor = when (order.status.lowercase()) {
                        "delivered" -> Color(0xFF16A34A)
                        "shipped" -> SpiritualRoyalBlue
                        "confirmed" -> SacredGoldDark
                        "cancelled" -> ActionCrimson
                        else -> Color(0xFFD97706)
                    }

                    Surface(
                        color = statusColor.copy(alpha = 0.15f),
                        shape = RoundedCornerShape(16.dp)
                    ) {
                        Text(
                            text = order.status.uppercase(),
                            color = statusColor,
                            fontWeight = FontWeight.Bold,
                            fontSize = 11.sp,
                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Timeline steps
                if (data.timeline.isNotEmpty()) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        data.timeline.forEach { step ->
                            Column(
                                horizontalAlignment = Alignment.CenterHorizontally,
                                modifier = Modifier.weight(1f)
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(26.dp)
                                        .clip(CircleShape)
                                        .background(if (step.completed || step.current) SpiritualRoyalBlue else Color.LightGray),
                                    contentAlignment = Alignment.Center
                                ) {
                                    if (step.completed) {
                                        Icon(Icons.Default.Check, contentDescription = null, tint = Color.White, modifier = Modifier.size(14.dp))
                                    } else {
                                        Text("•", fontSize = 14.sp, color = Color.White, fontWeight = FontWeight.Bold)
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = step.label,
                                    fontSize = 10.sp,
                                    fontWeight = if (step.current) FontWeight.Bold else FontWeight.Normal,
                                    color = if (step.completed || step.current) SpiritualRoyalBlue else Color.Gray
                                )
                            }
                        }
                    }
                }

                // Customer shipping details
                Divider(modifier = Modifier.padding(vertical = 12.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Text("Deliver To", fontSize = 11.sp, color = Color.Gray)
                        Text(order.shippingName ?: "Devotee", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        Text("${order.shippingCity ?: ""}, ${order.shippingState ?: ""}", fontSize = 12.sp, color = Color.Gray)
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text("Payment", fontSize = 11.sp, color = Color.Gray)
                        Text(order.paymentMethod ?: "COD", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        Text(order.paymentStatus.uppercase(), fontSize = 11.sp, color = if (order.paymentStatus == "paid") Color(0xFF16A34A) else Color(0xFFD97706), fontWeight = FontWeight.Bold)
                    }
                }
            }
        }

        // Ordered Items Card
        Card(
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Items in Parcel", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                Spacer(modifier = Modifier.height(10.dp))

                data.items.forEach { item ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(
                            text = "${item.productName} × ${item.quantity}",
                            fontSize = 13.sp,
                            modifier = Modifier.weight(1f)
                        )
                        Text(
                            text = "₹${item.total.toInt()}",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }

                Divider(modifier = Modifier.padding(vertical = 10.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Total Order Value", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                    Text(
                        text = "₹${order.total.toInt()}",
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = SpiritualRoyalBlue
                    )
                }
            }
        }
    }
}
