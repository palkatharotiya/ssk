package `in`.sskstore.kalakunj.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.ErrorView
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.theme.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun StoreInfoScreen(
    viewModel: SSKViewModel,
    onNavigateToPage: (String) -> Unit,
    onNavigateBack: () -> Unit
) {
    val context = LocalContext.current
    val storeInfoState by viewModel.storeInfoState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.loadStoreInfo()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Kalakunj Mandir & Info", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color.White)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = SpiritualRoyalBlue)
            )
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            when (val state = storeInfoState) {
                null, is Result.Loading -> LoadingView()
                is Result.Error -> ErrorView(
                    message = state.message,
                    onRetry = { viewModel.loadStoreInfo() }
                )
                is Result.Success -> {
                    val store = state.data.store
                    val pages = state.data.pages

                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        // Mandir Header Card
                        Card(
                            shape = RoundedCornerShape(14.dp),
                            colors = CardDefaults.cardColors(containerColor = SpiritualRoyalBlue),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(
                                modifier = Modifier.padding(20.dp),
                                horizontalAlignment = Alignment.CenterHorizontally
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(64.dp)
                                        .clip(CircleShape)
                                        .background(SacredGold),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text("ૐ", fontSize = 32.sp, fontWeight = FontWeight.Bold, color = SpiritualRoyalBlue)
                                }

                                Spacer(modifier = Modifier.height(12.dp))

                                Text(
                                    text = store.fullName,
                                    fontSize = 17.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color.White
                                )

                                Text(
                                    text = store.tagline,
                                    fontSize = 13.sp,
                                    color = SacredGoldLight
                                )

                                Spacer(modifier = Modifier.height(8.dp))

                                Text(
                                    text = store.address,
                                    fontSize = 12.sp,
                                    color = Color.White.copy(alpha = 0.85f),
                                    lineHeight = 16.sp,
                                    modifier = Modifier.padding(horizontal = 8.dp)
                                )
                            }
                        }

                        // Store / Mandir Timings Card
                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.AccessTime, contentDescription = null, tint = SpiritualRoyalBlue)
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Store & Satsang Timings", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                }

                                Spacer(modifier = Modifier.height(10.dp))

                                TimingRow(label = "Morning Session", time = store.hoursMorning)
                                Spacer(modifier = Modifier.height(6.dp))
                                TimingRow(label = "Evening Session", time = store.hoursEvening)
                                Spacer(modifier = Modifier.height(6.dp))
                                TimingRow(label = "Working Days", time = store.hoursDays)
                            }
                        }

                        // Quick Contact Buttons
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            Button(
                                onClick = {
                                    val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:${store.phone}"))
                                    context.startActivity(intent)
                                },
                                shape = RoundedCornerShape(8.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = SpiritualRoyalBlue),
                                modifier = Modifier
                                    .weight(1f)
                                    .height(44.dp)
                            ) {
                                Icon(Icons.Default.Phone, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Call Mandir", fontSize = 12.sp)
                            }

                            Button(
                                onClick = {
                                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(store.whatsappUrl))
                                    context.startActivity(intent)
                                },
                                shape = RoundedCornerShape(8.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF25D366)),
                                modifier = Modifier
                                    .weight(1f)
                                    .height(44.dp)
                            ) {
                                Icon(Icons.Default.Chat, contentDescription = null, modifier = Modifier.size(16.dp), tint = Color.White)
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("WhatsApp", fontSize = 12.sp, color = Color.White)
                            }
                        }

                        // UPI / Bank Details
                        if (!store.upiId.isNullOrBlank()) {
                            Card(
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = SacredGoldLight.copy(alpha = 0.25f)),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Text("Mandir Trust Account & UPI", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = SpiritualRoyalBlue)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text("UPI VPA: ${store.upiId}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                    if (!store.upiName.isNullOrBlank()) {
                                        Text("Beneficiary: ${store.upiName}", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                }
                            }
                        }

                        // Information & Policies List
                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(8.dp)) {
                                Text("Information & Policies", fontWeight = FontWeight.Bold, fontSize = 14.sp, modifier = Modifier.padding(10.dp))

                                pages.forEach { page ->
                                    ListItem(
                                        headlineContent = { Text(page.title, fontSize = 13.sp, fontWeight = FontWeight.Medium) },
                                        trailingContent = { Icon(Icons.Default.ChevronRight, contentDescription = null, tint = Color.Gray) },
                                        modifier = Modifier.clickable { onNavigateToPage(page.slug) }
                                    )
                                    Divider(color = MaterialTheme.colorScheme.surfaceVariant)
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(20.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun TimingRow(label: String, time: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(text = label, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(text = time, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = SpiritualRoyalBlue)
    }
}
