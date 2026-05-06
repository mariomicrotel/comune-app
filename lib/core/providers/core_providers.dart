import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/app_colors.dart';
import '../network/api_client.dart';
import '../../services/preferences_service.dart';
import '../../services/auth_service.dart';
import '../../services/notification_service.dart';
import '../../services/api_notification_service.dart';
import '../../services/avvisi_service.dart';
import '../../services/segnalazioni_service.dart';
import '../../services/eventi_service.dart';
import '../../services/uffici_service.dart';
import '../../services/luoghi_service.dart';
import '../../services/rifiuti_service.dart';
import '../../services/documenti_service.dart';
import '../../services/sondaggi_service.dart';

// SharedPreferences — overridden in main.dart
final sharedPreferencesProvider = Provider<SharedPreferences>(
  (_) => throw UnimplementedError('Override in ProviderScope'),
);

final preferencesServiceProvider = Provider<PreferencesService>(
  (ref) => PreferencesService(ref.watch(sharedPreferencesProvider)),
);

final dioProvider = Provider<Dio>(
  (ref) => createDio(ref.watch(sharedPreferencesProvider)),
);

final authServiceProvider = Provider<AuthService>(
  (ref) => AuthService(ref.watch(preferencesServiceProvider)),
);

final apiNotificationServiceProvider = Provider<ApiNotificationService>(
  (ref) => ApiNotificationService(ref.watch(dioProvider)),
);

final notificationServiceProvider = Provider<NotificationService>(
  (ref) => NotificationService(
    ref.watch(preferencesServiceProvider),
    ref.watch(apiNotificationServiceProvider),
  ),
);

final avvisiServiceProvider = Provider<AvvisiService>(
  (ref) => AvvisiService(ref.watch(dioProvider)),
);

final segnalazioniServiceProvider = Provider<SegnalazioniService>(
  (ref) => SegnalazioniService(ref.watch(dioProvider)),
);

final eventiServiceProvider = Provider<EventiService>(
  (ref) => EventiService(ref.watch(dioProvider)),
);

final ufficiServiceProvider = Provider<UfficiService>(
  (ref) => UfficiService(ref.watch(dioProvider)),
);

final luoghiServiceProvider = Provider<LuoghiService>(
  (ref) => LuoghiService(ref.watch(dioProvider)),
);

final rifiutiServiceProvider = Provider<RifiutiService>(
  (ref) => RifiutiService(ref.watch(dioProvider)),
);

final documentiServiceProvider = Provider<DocumentiService>(
  (ref) => DocumentiService(ref.watch(dioProvider)),
);

final sondaggiServiceProvider = Provider<SondaggiService>(
  (ref) => SondaggiService(ref.watch(dioProvider)),
);

// ── Active palette provider ─────────────────────────────────────────────────

final activePaletteProvider = Provider<AppPalette>((ref) {
  final key = ref.watch(preferencesServiceProvider).palette;
  return switch (key) {
    'bluSavoia' => AppPalette.bluSavoia,
    'verdeBorgo' => AppPalette.verdeBorgo,
    'tricolore' => AppPalette.tricolore,
    _ => AppPalette.bluCivico,
  };
});
