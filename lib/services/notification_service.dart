import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'preferences_service.dart';
import 'api_notification_service.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Background handler — no UI work here
}

class NotificationService {
  final PreferencesService _prefs;
  final ApiNotificationService _api;
  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  // Deep-link callback invoked when user taps a push
  void Function(String type, String id)? onDeepLink;

  NotificationService(this._prefs, this._api);

  Future<void> initialize() async {
    // Local notifications setup
    const androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );
    await _localNotifications.initialize(
      settings: const InitializationSettings(
          android: androidSettings, iOS: iosSettings),
      onDidReceiveNotificationResponse: _onLocalTap,
    );

    // Create Android channel
    const channel = AndroidNotificationChannel(
      'cam_channel',
      'Comune App',
      description: 'Notifiche dal Comune',
      importance: Importance.high,
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);

    // FCM background handler
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    // Foreground messages → show local notification
    FirebaseMessaging.onMessage.listen(_onForegroundMessage);

    // Tap from background
    FirebaseMessaging.onMessageOpenedApp.listen(_handleDeepLink);

    // Tap from terminated
    final initial = await _fcm.getInitialMessage();
    if (initial != null) _handleDeepLink(initial);
  }

  Future<void> requestPermissionAndRegister() async {
    final settings = await _fcm.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    final granted = settings.authorizationStatus == AuthorizationStatus.authorized ||
        settings.authorizationStatus == AuthorizationStatus.provisional;

    await _prefs.setNotificationsConsent(granted);
    if (!granted) return;

    final token = await _fcm.getToken();
    if (token == null) return;

    await _prefs.setFcmToken(token);
    final deviceId = _prefs.getOrCreateDeviceId();
    final platform = Platform.isIOS ? 'ios' : 'android';

    await _api.registerToken(
      token: token,
      deviceId: deviceId,
      platform: platform,
    );

    // Refresh token listener
    _fcm.onTokenRefresh.listen((newToken) async {
      await _prefs.setFcmToken(newToken);
      await _api.registerToken(
        token: newToken,
        deviceId: deviceId,
        platform: platform,
      );
    });
  }

  void _onForegroundMessage(RemoteMessage message) {
    final notification = message.notification;
    if (notification == null) return;

    _localNotifications.show(
      id: notification.hashCode,
      title: notification.title,
      body: notification.body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          'cam_channel',
          'Comune App',
          channelDescription: 'Notifiche dal Comune',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
      payload: _payloadFrom(message.data),
    );
  }

  void _onLocalTap(NotificationResponse response) {
    final payload = response.payload;
    if (payload == null) return;
    final parts = payload.split(':');
    if (parts.length == 2) onDeepLink?.call(parts[0], parts[1]);
  }

  void _handleDeepLink(RemoteMessage message) {
    final type = message.data['deep_link_type'] as String?;
    final id = message.data['deep_link_id'] as String?;
    if (type != null && id != null) onDeepLink?.call(type, id);
  }

  String _payloadFrom(Map<String, dynamic> data) {
    final type = data['deep_link_type'] ?? 'home';
    final id = data['deep_link_id'] ?? '0';
    return '$type:$id';
  }
}
