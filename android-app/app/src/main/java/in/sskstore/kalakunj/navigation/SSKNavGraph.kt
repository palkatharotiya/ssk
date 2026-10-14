package `in`.sskstore.kalakunj.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import `in`.sskstore.kalakunj.ui.screens.*
import `in`.sskstore.kalakunj.viewmodel.SSKViewModel

@Composable
fun SSKNavGraph(
    navController: NavHostController,
    viewModel: SSKViewModel
) {
    NavHost(
        navController = navController,
        startDestination = Screen.Splash.route
    ) {
        composable(Screen.Splash.route) {
            SplashScreen(
                onNavigateToHome = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.Splash.route) { inclusive = true }
                    }
                },
                viewModel = viewModel
            )
        }

        composable(Screen.Home.route) {
            HomeScreen(
                viewModel = viewModel,
                onNavigateToCatalog = { dept, search, onSale ->
                    navController.navigate(Screen.Catalog.createRoute(dept, search, onSale))
                },
                onNavigateToProduct = { productId ->
                    navController.navigate(Screen.ProductDetail.createRoute(productId))
                },
                onNavigateToCart = { navController.navigate(Screen.Cart.route) },
                onNavigateToAuth = { navController.navigate(Screen.Auth.route) },
                onNavigateToTrackOrder = { navController.navigate(Screen.TrackOrder.route) },
                onNavigateToStoreInfo = { navController.navigate(Screen.StoreInfo.route) }
            )
        }

        composable(
            route = Screen.Catalog.route,
            arguments = listOf(
                navArgument("dept") { type = NavType.StringType; defaultValue = "" },
                navArgument("search") { type = NavType.StringType; defaultValue = "" },
                navArgument("onSale") { type = NavType.StringType; defaultValue = "false" }
            )
        ) { backStackEntry ->
            val dept = backStackEntry.arguments?.getString("dept")?.toIntOrNull()
            val search = backStackEntry.arguments?.getString("search")?.takeIf { it.isNotBlank() }
            val onSale = backStackEntry.arguments?.getString("onSale") == "true"

            CatalogScreen(
                viewModel = viewModel,
                initialDept = dept,
                initialSearch = search,
                initialOnSale = onSale,
                onNavigateToProduct = { productId ->
                    navController.navigate(Screen.ProductDetail.createRoute(productId))
                },
                onNavigateToCart = { navController.navigate(Screen.Cart.route) },
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.ProductDetail.route,
            arguments = listOf(navArgument("productId") { type = NavType.IntType })
        ) { backStackEntry ->
            val productId = backStackEntry.arguments?.getInt("productId") ?: 0
            ProductDetailScreen(
                viewModel = viewModel,
                productId = productId,
                onNavigateToCart = { navController.navigate(Screen.Cart.route) },
                onNavigateToProduct = { id ->
                    navController.navigate(Screen.ProductDetail.createRoute(id))
                },
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Cart.route) {
            CartScreen(
                viewModel = viewModel,
                onNavigateToCheckout = { navController.navigate(Screen.Checkout.route) },
                onNavigateToProduct = { id ->
                    navController.navigate(Screen.ProductDetail.createRoute(id))
                },
                onNavigateToCatalog = {
                    navController.navigate(Screen.Catalog.createRoute())
                },
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Checkout.route) {
            CheckoutScreen(
                viewModel = viewModel,
                onOrderSuccess = { orderNumber ->
                    navController.navigate(Screen.OrderSuccess.createRoute(orderNumber)) {
                        popUpTo(Screen.Home.route)
                    }
                },
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.OrderSuccess.route,
            arguments = listOf(navArgument("orderNumber") { type = NavType.StringType })
        ) { backStackEntry ->
            val orderNumber = backStackEntry.arguments?.getString("orderNumber") ?: ""
            OrderSuccessScreen(
                orderNumber = orderNumber,
                onNavigateToTrackOrder = {
                    navController.navigate(Screen.TrackOrder.route)
                },
                onNavigateToHome = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.Home.route) { inclusive = true }
                    }
                }
            )
        }

        composable(Screen.TrackOrder.route) {
            TrackOrderScreen(
                viewModel = viewModel,
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Auth.route) {
            AuthScreen(
                viewModel = viewModel,
                onNavigateBack = { navController.popBackStack() },
                onAuthSuccess = { navController.popBackStack() }
            )
        }

        composable(Screen.StoreInfo.route) {
            StoreInfoScreen(
                viewModel = viewModel,
                onNavigateToPage = { slug ->
                    navController.navigate(Screen.PageDetail.createRoute(slug))
                },
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.PageDetail.route,
            arguments = listOf(navArgument("slug") { type = NavType.StringType })
        ) { backStackEntry ->
            val slug = backStackEntry.arguments?.getString("slug") ?: ""
            PageDetailScreen(
                viewModel = viewModel,
                pageSlug = slug,
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}
