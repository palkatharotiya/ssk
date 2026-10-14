package `in`.sskstore.kalakunj.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import `in`.sskstore.kalakunj.data.model.OrderItemRequest
import `in`.sskstore.kalakunj.data.model.OrderRequest
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CheckoutScreen(
    viewModel: SSKViewModel,
    onOrderSuccess: (String) -> Unit,
    onNavigateBack: () -> Unit
) {
    val customer by viewModel.customer.collectAsState()
    val cartItems by viewModel.cartItems.collectAsState()
    val cartSubtotal by viewModel.cartSubtotal.collectAsState()
    val orderState by viewModel.orderState.collectAsState()

    val shippingFee = if (cartSubtotal >= 500) 0.0 else 49.0
    val totalAmount = cartSubtotal + shippingFee

    // Form fields prefilled if logged in
    var firstName by remember { mutableStateOf(customer?.firstName ?: "") }
    var lastName by remember { mutableStateOf(customer?.lastName ?: "") }
    var email by remember { mutableStateOf(customer?.email ?: "") }
    var phone by remember { mutableStateOf(customer?.phone ?: "") }
    var address1 by remember { mutableStateOf(customer?.addressLine1 ?: "") }
    var address2 by remember { mutableStateOf(customer?.addressLine2 ?: "") }
    var city by remember { mutableStateOf(customer?.city ?: "Surat") }
    var state by remember { mutableStateOf(customer?.state ?: "Gujarat") }
    var pincode by remember { mutableStateOf(customer?.pincode ?: "") }
    var notes by remember { mutableStateOf("") }
    var paymentMethod by remember { mutableStateOf("cod") }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    // Observe order placement
    LaunchedEffect(orderState) {
        when (val res = orderState) {
            is Result.Success -> {
                viewModel.clearOrderState()
                onOrderSuccess(res.data.orderNumber)
            }
            is Result.Error -> {
                errorMessage = res.message
            }
            else -> {}
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Checkout & Shipping", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color.White)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = SpiritualRoyalBlue)
            )
        },
        bottomBar = {
            Surface(
                tonalElevation = 8.dp,
                shadowElevation = 8.dp,
                color = MaterialTheme.colorScheme.surface
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Button(
                        onClick = {
                            if (firstName.isBlank() || phone.isBlank() || address1.isBlank() || pincode.isBlank()) {
                                errorMessage = "Please fill in all mandatory delivery details (*)"
                                return@Button
                            }
                            errorMessage = null

                            val fullAddress = if (address2.isNotBlank()) "$address1, $address2" else address1
                            val request = OrderRequest(
                                firstName = firstName.trim(),
                                lastName = if (lastName.isBlank()) "Devotee" else lastName.trim(),
                                email = if (email.isBlank()) "devotee@sskstore.in" else email.trim(),
                                phone = phone.trim(),
                                address = fullAddress.trim(),
                                city = city.trim(),
                                state = state.trim(),
                                pincode = pincode.trim(),
                                paymentMethod = if (paymentMethod == "cod") "COD" else "UPI",
                                notes = notes.trim(),
                                items = cartItems.map {
                                    OrderItemRequest(
                                        productId = it.product.id,
                                        quantity = it.quantity
                                    )
                                }
                            )
                            viewModel.createOrder(request)
                        },
                        enabled = orderState !is Result.Loading && cartItems.isNotEmpty(),
                        shape = RoundedCornerShape(8.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = SpiritualRoyalBlue,
                            contentColor = Color.White
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(48.dp)
                    ) {
                        if (orderState is Result.Loading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Placing Order...")
                        } else {
                            Text(
                                "Place Sacred Order • ₹${totalAmount.toInt()}",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp
                            )
                        }
                    }
                }
            }
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
            // Error banner
            if (errorMessage != null) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFEE2E2)),
                    shape = RoundedCornerShape(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = errorMessage ?: "",
                        color = ActionCrimson,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Medium,
                        modifier = Modifier.padding(12.dp)
                    )
                }
            }

            // Customer Info Card
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("1. Recipient Details", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = SpiritualRoyalBlue)
                    Spacer(modifier = Modifier.height(12.dp))

                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = firstName,
                            onValueChange = { firstName = it },
                            label = { Text("First Name *") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = lastName,
                            onValueChange = { lastName = it },
                            label = { Text("Last Name") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        label = { Text("Mobile Number (WhatsApp) *") },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = email,
                        onValueChange = { email = it },
                        label = { Text("Email Address") },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
            }

            // Delivery Address Card
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("2. Delivery Address", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = SpiritualRoyalBlue)
                    Spacer(modifier = Modifier.height(12.dp))

                    OutlinedTextField(
                        value = address1,
                        onValueChange = { address1 = it },
                        label = { Text("Flat / House No., Society / Building *") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = address2,
                        onValueChange = { address2 = it },
                        label = { Text("Landmark, Street Area") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = city,
                            onValueChange = { city = it },
                            label = { Text("City *") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = pincode,
                            onValueChange = { pincode = it },
                            label = { Text("Pincode *") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = state,
                        onValueChange = { state = it },
                        label = { Text("State") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
            }

            // Payment Mode
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("3. Payment Option", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = SpiritualRoyalBlue)
                    Spacer(modifier = Modifier.height(8.dp))

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { paymentMethod = "cod" }
                            .padding(vertical = 4.dp)
                    ) {
                        RadioButton(selected = paymentMethod == "cod", onClick = { paymentMethod = "cod" })
                        Spacer(modifier = Modifier.width(8.dp))
                        Column {
                            Text("Cash / UPI on Delivery", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                            Text("Pay conveniently when your sacred parcel arrives", fontSize = 11.sp, color = Color.Gray)
                        }
                    }

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { paymentMethod = "bank_transfer" }
                            .padding(vertical = 4.dp)
                    ) {
                        RadioButton(selected = paymentMethod == "bank_transfer", onClick = { paymentMethod = "bank_transfer" })
                        Spacer(modifier = Modifier.width(8.dp))
                        Column {
                            Text("Direct Mandir Trust Transfer / UPI", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                            Text("Account details provided on next screen", fontSize = 11.sp, color = Color.Gray)
                        }
                    }
                }
            }

            // Order items preview
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Items Summary (${cartItems.size} unique)", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                    Spacer(modifier = Modifier.height(8.dp))
                    cartItems.forEach { item ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 3.dp),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("${item.product.name} × ${item.quantity}", fontSize = 12.sp, modifier = Modifier.weight(1f))
                            Text("₹${item.lineTotal.toInt()}", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }
                    Divider(modifier = Modifier.padding(vertical = 8.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Subtotal", fontSize = 12.sp)
                        Text("₹${cartSubtotal.toInt()}", fontSize = 12.sp)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Shipping", fontSize = 12.sp)
                        Text(if (shippingFee == 0.0) "FREE" else "₹${shippingFee.toInt()}", fontSize = 12.sp)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Total", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                        Text("₹${totalAmount.toInt()}", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = SpiritualRoyalBlue)
                    }
                }
            }

            Spacer(modifier = Modifier.height(20.dp))
        }
    }
}
