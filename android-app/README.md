# SSK Store — Official Android App (Shri Sahajanand Sahitya Kendra • Kalakunj Mandir)

An Android application for **SSK.STORE** (Shri Sahajanand Sahitya Kendra — Kalakunj Mandir) featuring high-resolution 3D branding, responsive WebView, offline mode, and instant UPI intent routing.

## Architecture & Features

- **Package Name**: `store.baps`
- **Minimum SDK**: API 21 (Android 5.0 Lollipop)
- **Target SDK**: API 34 (Android 14)
- **Engine**: Accelerated WebKit WebView with hardware acceleration
- **Pull to Refresh**: Material `SwipeRefreshLayout` with custom theme colors
- **Progress Tracking**: Top progress bar tracking page load progress
- **Offline Mode**: Native offline screen with retry connection button
- **Hardware Back Button**: Traverses website browsing history before exiting
- **Mobile Payments**: Direct native intent delegation for UPI (`upi://`), phone calls (`tel:`), WhatsApp (`whatsapp://`), and email (`mailto:`)
- **JavaScript Bridge**: Native `WebAppInterface` exposing Android share dialogs and toasts to JavaScript

## Project Structure

```
android-app/
├── app/
│   ├── build.gradle
│   └── src/main/
│       ├── AndroidManifest.xml
│       ├── java/store/baps/
│       │   ├── MainActivity.java
│       │   └── WebAppInterface.java
│       └── res/
│           ├── layout/activity_main.xml
│           ├── values/colors.xml
│           ├── values/strings.xml
│           ├── values/styles.xml
│           └── drawable/progress_drawable.xml
├── build.gradle
├── settings.gradle
└── gradle.properties
```

## How to Build & Run

### Method 1: Using Android Studio
1. Open **Android Studio**.
2. Select **Open** and choose the `android-app` folder.
3. Allow Gradle to sync dependencies.
4. Select a connected device or Android Emulator.
5. Click **Run** (`Shift + F10`) or click the green Play button.

### Method 2: Building via Command Line (Gradle)
```bash
cd "d:\.King Bro\XEROX\baps-store\android-app"
./gradlew assembleDebug
```
The compiled debug APK will be generated at:
`app/build/outputs/apk/debug/app-debug.apk`

### Testing with Local PHP Server on Android Emulator
When running the PHP server locally on your PC (`php -S 0.0.0.0:8000`), update `STORE_URL` in `MainActivity.java`:
```java
private static final String STORE_URL = "http://10.0.2.2:8000/";
```
(In the Android emulator, `10.0.2.2` automatically points to `localhost` on the host machine).
