package `in`.sskstore.kalakunj

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.viewModels
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.navigation.compose.rememberNavController
import `in`.sskstore.kalakunj.navigation.SSKNavGraph
import `in`.sskstore.kalakunj.ui.theme.SSKStoreTheme
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

class MainActivity : ComponentActivity() {

    private val viewModel: SSKViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            val isDarkTheme by viewModel.isDarkMode.collectAsState()
            val snackbarHostState = remember { SnackbarHostState() }
            val navController = rememberNavController()

            // Observe snackbar messages
            LaunchedEffect(Unit) {
                viewModel.snackbarMessage.collect { message ->
                    snackbarHostState.showSnackbar(message)
                }
            }

            SSKStoreTheme(darkTheme = isDarkTheme) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    Scaffold(
                        snackbarHost = { SnackbarHost(snackbarHostState) }
                    ) { innerPadding ->
                        Surface(modifier = Modifier.padding(innerPadding)) {
                            SSKNavGraph(
                                navController = navController,
                                viewModel = viewModel
                            )
                        }
                    }
                }
            }
        }
    }
}
