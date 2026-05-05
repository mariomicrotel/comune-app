import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/core_providers.dart';
import '../screens/splash_screen.dart';
import '../screens/onboarding_privacy_screen.dart';
import '../../features/home/home_screen.dart';
import '../../features/avvisi/avvisi_list_screen.dart';
import '../../features/avvisi/avviso_detail_screen.dart';
import '../../features/segnalazioni/segnalazioni_list_screen.dart';
import '../../features/segnalazioni/segnalazione_create_screen.dart';
import '../../features/segnalazioni/segnalazione_detail_screen.dart';
import '../../features/eventi/eventi_list_screen.dart';
import '../../features/eventi/evento_detail_screen.dart';
import '../../features/uffici/uffici_list_screen.dart';
import '../../features/luoghi/luoghi_map_screen.dart';
import '../../features/rifiuti/rifiuti_screen.dart';
import '../../features/documenti/documenti_screen.dart';
import '../../features/sondaggi/sondaggi_list_screen.dart';
import '../../features/sondaggi/sondaggio_detail_screen.dart';
import '../../features/profilo/profilo_screen.dart';
import '../../features/profilo/settings_screen.dart';
import '../../features/notifiche/notifiche_screen.dart';
import '../../widgets/comune_bottom_navigation.dart';
import '../../widgets/error_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final prefs = ref.watch(preferencesServiceProvider);

  return GoRouter(
    initialLocation: '/splash',
    errorBuilder: (context, state) =>
        ErrorScreen(error: state.error, onRetry: () => context.go('/home')),
    redirect: (context, state) {
      final loc = state.matchedLocation;
      if (loc == '/splash') return null;
      if (!prefs.privacyConsent && loc != '/onboarding') return '/onboarding';
      if (prefs.privacyConsent && loc == '/onboarding') return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
      GoRoute(path: '/onboarding', builder: (_, __) => const OnboardingPrivacyScreen()),

      // ── Shell route — bottom nav ──────────────────────────────────────────
      StatefulShellRoute.indexedStack(
        builder: (context, state, shell) =>
            ComuneBottomNavigation(navigationShell: shell),
        branches: [
          // Branch 0: Home
          StatefulShellBranch(routes: [
            GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
          ]),
          // Branch 1: Avvisi
          StatefulShellBranch(routes: [
            GoRoute(
              path: '/avvisi',
              builder: (_, __) => const AvvisiListScreen(),
              routes: [
                GoRoute(
                  path: ':id',
                  builder: (_, state) => AvvisoDetailScreen(
                    id: int.parse(state.pathParameters['id']!),
                  ),
                ),
              ],
            ),
          ]),
          // Branch 2: Mappa (Segnala is a modal push, not a shell branch)
          StatefulShellBranch(routes: [
            GoRoute(
                path: '/luoghi/mappa',
                builder: (_, __) => const LuoghiMapScreen()),
          ]),
          // Branch 3: Profilo
          StatefulShellBranch(routes: [
            GoRoute(
              path: '/profilo',
              builder: (_, __) => const ProfiloScreen(),
              routes: [
                GoRoute(
                  path: 'settings',
                  builder: (_, __) => const SettingsScreen(),
                ),
              ],
            ),
          ]),
        ],
      ),

      // ── Full-screen modal / push routes (outside shell) ───────────────────
      GoRoute(
          path: '/segnala/new',
          builder: (_, __) => const SegnalazioneCreateScreen()),
      GoRoute(
        path: '/segnalazioni',
        builder: (_, __) => const SegnalazioniListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (_, state) => SegnalazioneDetailScreen(
              id: state.pathParameters['id']!,
            ),
          ),
        ],
      ),
      GoRoute(
        path: '/eventi',
        builder: (_, __) => const EventiListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (_, state) => EventoDetailScreen(
              id: int.parse(state.pathParameters['id']!),
            ),
          ),
        ],
      ),
      GoRoute(path: '/uffici', builder: (_, __) => const UfficiListScreen()),
      GoRoute(path: '/rifiuti', builder: (_, __) => const RifiutiScreen()),
      GoRoute(path: '/documenti', builder: (_, __) => const DocumentiScreen()),
      GoRoute(
        path: '/sondaggi',
        builder: (_, __) => const SondaggiListScreen(),
        routes: [
          GoRoute(
            path: ':id',
            builder: (_, state) => SondaggioDetailScreen(
              id: int.parse(state.pathParameters['id']!),
            ),
          ),
        ],
      ),
      GoRoute(
          path: '/notifiche',
          builder: (_, __) => const NotificheScreen()),
    ],
  );
});
