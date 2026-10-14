package `in`.sskstore.kalakunj.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp
import androidx.core.view.WindowCompat

private val LightColorScheme = lightColorScheme(
    primary = SpiritualRoyalBlue,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFD4E2FF),
    onPrimaryContainer = SpiritualBlueDark,
    secondary = SacredGold,
    onSecondary = Color.Black,
    secondaryContainer = Color(0xFFFFF3D0),
    onSecondaryContainer = SacredGoldDark,
    tertiary = ActionCrimson,
    onTertiary = Color.White,
    error = ActionCrimson,
    onError = Color.White,
    background = CreamSurface,
    onBackground = TextPrimary,
    surface = Color.White,
    onSurface = TextPrimary,
    surfaceVariant = CreamSurfaceVariant,
    onSurfaceVariant = TextSecondary,
    outline = DividerColor,
)

private val DarkColorScheme = darkColorScheme(
    primary = Color(0xFF8AB4F8),
    onPrimary = SpiritualBlueDark,
    primaryContainer = SpiritualRoyalBlue,
    onPrimaryContainer = Color(0xFFD4E2FF),
    secondary = SacredGoldLight,
    onSecondary = Color.Black,
    secondaryContainer = SacredGoldDark,
    onSecondaryContainer = Color(0xFFFFF3D0),
    tertiary = ActionCrimsonLight,
    onTertiary = Color.White,
    error = ActionCrimsonLight,
    onError = Color.Black,
    background = DarkBackground,
    onBackground = DarkTextPrimary,
    surface = DarkSurface,
    onSurface = DarkTextPrimary,
    surfaceVariant = DarkSurfaceVariant,
    onSurfaceVariant = DarkTextSecondary,
    outline = DarkDivider,
)

private val SSKTypography = Typography(
    headlineLarge = Typography().headlineLarge.copy(
        fontWeight = FontWeight.Bold,
        letterSpacing = (-0.5).sp
    ),
    headlineMedium = Typography().headlineMedium.copy(
        fontWeight = FontWeight.Bold,
    ),
    titleLarge = Typography().titleLarge.copy(
        fontWeight = FontWeight.SemiBold,
    ),
    titleMedium = Typography().titleMedium.copy(
        fontWeight = FontWeight.SemiBold,
    ),
    bodyLarge = Typography().bodyLarge.copy(
        lineHeight = 24.sp
    ),
    labelLarge = Typography().labelLarge.copy(
        fontWeight = FontWeight.SemiBold,
        letterSpacing = 0.5.sp
    ),
)

@Composable
fun SSKStoreTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme

    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = if (darkTheme) DarkBackground.toArgb() else SpiritualRoyalBlue.toArgb()
            window.navigationBarColor = if (darkTheme) DarkBackground.toArgb() else Color.White.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = false
            WindowCompat.getInsetsController(window, view).isAppearanceLightNavigationBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = SSKTypography,
        content = content
    )
}
