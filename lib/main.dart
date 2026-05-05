import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'core/constants/app_colors.dart';
import 'core/providers/core_providers.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'firebase_options.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('it_IT', null);
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  final sharedPreferences = await SharedPreferences.getInstance();

  runApp(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(sharedPreferences),
      ],
      child: const ComuneApp(),
    ),
  );
}

class ComuneApp extends ConsumerWidget {
  const ComuneApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final prefs = ref.watch(preferencesServiceProvider);

    final palette = switch (prefs.palette) {
      'bluSavoia' => AppPalette.bluSavoia,
      'verdeBorgo' => AppPalette.verdeBorgo,
      'tricolore' => AppPalette.tricolore,
      _ => AppPalette.bluCivico,
    };

    final themeMode = switch (prefs.themeMode) {
      'light' => ThemeMode.light,
      'dark' => ThemeMode.dark,
      _ => ThemeMode.system,
    };

    return MaterialApp.router(
      title: 'Comune App',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(palette: palette),
      darkTheme: AppTheme.dark(palette: palette),
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
