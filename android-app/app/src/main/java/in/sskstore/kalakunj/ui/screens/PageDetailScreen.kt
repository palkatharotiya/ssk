package `in`.sskstore.kalakunj.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import `in`.sskstore.kalakunj.data.repository.Result
import `in`.sskstore.kalakunj.ui.components.ErrorView
import `in`.sskstore.kalakunj.ui.components.LoadingView
import `in`.sskstore.kalakunj.ui.theme.SpiritualRoyalBlue
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageDetailScreen(
    viewModel: SSKViewModel,
    pageSlug: String,
    onNavigateBack: () -> Unit
) {
    val storeInfoState by viewModel.storeInfoState.collectAsState()

    LaunchedEffect(Unit) {
        if (storeInfoState == null) {
            viewModel.loadStoreInfo()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    val pageTitle = (storeInfoState as? Result.Success)?.data?.pages?.find { it.slug == pageSlug }?.title ?: "Policy & Info"
                    Text(pageTitle, fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color.White)
                },
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
                    val page = state.data.pages.find { it.slug == pageSlug }
                    if (page == null) {
                        Text(
                            text = "Page content not found.",
                            modifier = Modifier.padding(20.dp),
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    } else {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .verticalScroll(rememberScrollState())
                                .padding(18.dp)
                        ) {
                            Text(
                                text = page.title,
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold,
                                color = SpiritualRoyalBlue
                            )

                            Spacer(modifier = Modifier.height(10.dp))
                            Divider()
                            Spacer(modifier = Modifier.height(14.dp))

                            // Clean plain text rendering of content
                            val cleanContent = page.content
                                ?.replace(Regex("<br\\s*/?>"), "\n")
                                ?.replace(Regex("<p>"), "")
                                ?.replace(Regex("</p>"), "\n\n")
                                ?.replace(Regex("<[^>]*>"), "")
                                ?: "Information will be updated soon."

                            Card(
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(
                                    text = cleanContent.trim(),
                                    fontSize = 14.sp,
                                    lineHeight = 22.sp,
                                    color = MaterialTheme.colorScheme.onSurface,
                                    modifier = Modifier.padding(16.dp)
                                )
                            }

                            Spacer(modifier = Modifier.height(24.dp))
                        }
                    }
                }
            }
        }
    }
}
