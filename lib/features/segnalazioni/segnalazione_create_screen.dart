import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../widgets/primary_button.dart';
import 'segnalazioni_notifier.dart';

class SegnalazioneCreateScreen extends ConsumerStatefulWidget {
  const SegnalazioneCreateScreen({super.key});

  @override
  ConsumerState<SegnalazioneCreateScreen> createState() =>
      _SegnalazioneCreateScreenState();
}

class _SegnalazioneCreateScreenState
    extends ConsumerState<SegnalazioneCreateScreen> {
  int _step = 0; // 0..3
  bool _submitting = false;
  // Step 3
  final _descController = TextEditingController();
  bool _detectingLocation = false;
  // Voice recording simulation
  bool _recording = false;
  int _recordingSeconds = 0;
  Timer? _recordingTimer;

  @override
  void dispose() {
    _descController.dispose();
    _recordingTimer?.cancel();
    super.dispose();
  }

  AppColorTokens get _colors {
    return AppColors.resolve(
      Theme.of(context).brightness,
      ref.watch(activePaletteProvider),
    );
  }

  void _nextStep() => setState(() => _step++);
  void _prevStep() {
    if (_step > 0) setState(() => _step--);
  }

  Future<void> _detectLocation() async {
    setState(() => _detectingLocation = true);
    try {
      LocationPermission perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.deniedForever) return;
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );
      ref.read(segnalazioneFormProvider.notifier).setLocation(
            pos.latitude,
            pos.longitude,
            'Via Roma 14, Acerno', // would be reverse-geocoded in production
          );
    } finally {
      if (mounted) setState(() => _detectingLocation = false);
    }
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    try {
      final form = ref.read(segnalazioneFormProvider);
      final deviceId =
          ref.read(preferencesServiceProvider).getOrCreateDeviceId();
      await ref.read(segnalazioniServiceProvider).submit(
            categoria: form.categoria!,
            descrizione: _descController.text,
            deviceId: deviceId,
            lat: form.lat,
            lng: form.lng,
            foto: form.foto.isEmpty ? null : form.foto,
          );
      ref.read(segnalazioneFormProvider.notifier).reset();
      ref.invalidate(mySegnalazioniProvider);
      if (mounted) setState(() => _step = 4); // success
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final form = ref.watch(segnalazioneFormProvider);
    final mySegnalazioniAsync = ref.watch(mySegnalazioniProvider);
    final mieCount = mySegnalazioniAsync.maybeWhen(
        data: (l) => l.length, orElse: () => 0);

    return Scaffold(
      appBar: _step < 4
          ? AppBar(
              title: const Text('Segnala un problema'),
              leading: _step > 0
                  ? IconButton(
                      icon: const Icon(Icons.arrow_back),
                      onPressed: _prevStep,
                    )
                  : null,
              actions: [
                if (mieCount > 0)
                  Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: OutlinedButton(
                      onPressed: () => context.push('/segnalazioni'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        minimumSize: const Size(0, 36),
                        textStyle: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      child: Text('Le mie ($mieCount)'),
                    ),
                  ),
              ],
            )
          : null,
      body: Column(
        children: [
          if (_step < 4) _StepBar(step: _step, colors: _colors),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 220),
              child: switch (_step) {
                0 => _Step1(
                    key: const ValueKey(0),
                    colors: _colors,
                    onSelect: (cat) {
                      ref.read(segnalazioneFormProvider.notifier).setCategoria(cat);
                      Future.delayed(
                        const Duration(milliseconds: 180),
                        _nextStep,
                      );
                    },
                  ),
                1 => _Step2(
                    key: const ValueKey(1),
                    colors: _colors,
                    form: form,
                    recording: _recording,
                    recordingSeconds: _recordingSeconds,
                    onAddPhoto: () async {
                      final xf = await ImagePicker()
                          .pickImage(source: ImageSource.camera, imageQuality: 80);
                      if (xf != null) {
                        ref
                            .read(segnalazioneFormProvider.notifier)
                            .addFoto(File(xf.path));
                      }
                    },
                    onAddGallery: () async {
                      final xf = await ImagePicker()
                          .pickImage(source: ImageSource.gallery, imageQuality: 80);
                      if (xf != null) {
                        ref
                            .read(segnalazioneFormProvider.notifier)
                            .addFoto(File(xf.path));
                      }
                    },
                    onRemoveFoto: (i) =>
                        ref.read(segnalazioneFormProvider.notifier).removeFoto(i),
                    onToggleRecording: () {
                      if (_recording) {
                        _recordingTimer?.cancel();
                        setState(() {
                          _recording = false;
                          _recordingSeconds = 0;
                        });
                      } else {
                        setState(() {
                          _recording = true;
                          _recordingSeconds = 0;
                        });
                        _recordingTimer = Timer.periodic(
                          const Duration(seconds: 1),
                          (_) => setState(() => _recordingSeconds++),
                        );
                      }
                    },
                    onContinue: _nextStep,
                  ),
                2 => _Step3(
                    key: const ValueKey(2),
                    colors: _colors,
                    form: form,
                    descController: _descController,
                    detectingLocation: _detectingLocation,
                    onDetectLocation: _detectLocation,
                    onSubmit: _nextStep, // goes to review step
                  ),
                3 => _Step4Review(
                    key: const ValueKey(3),
                    colors: _colors,
                    form: form,
                    descrizione: _descController.text,
                    submitting: _submitting,
                    onSubmit: _submit,
                    onEdit: _prevStep,
                  ),
                _ => _SuccessStep(key: const ValueKey(4), colors: _colors),
              },
            ),
          ),
        ],
      ),
    );
  }
}

// ── Step bar ──────────────────────────────────────────────────────────────────

class _StepBar extends StatelessWidget {
  final int step;
  final AppColorTokens colors;

  const _StepBar({required this.step, required this.colors});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSizes.padX, 10, AppSizes.padX, 0),
      child: Row(
        children: List.generate(4, (i) {
          final done = i <= step;
          return Expanded(
            child: Padding(
              padding: EdgeInsets.only(right: i < 3 ? 6 : 0),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                height: 5,
                decoration: BoxDecoration(
                  color: done ? colors.primary : colors.border,
                  borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}

// ── Step 1: Category ──────────────────────────────────────────────────────────

class _Step1 extends StatelessWidget {
  final AppColorTokens colors;
  final ValueChanged<String> onSelect;

  const _Step1({super.key, required this.colors, required this.onSelect});

  static const _categories = [
    ('buche_strade', 'Buche e strade', Icons.directions_car_outlined),
    ('illuminazione', 'Illuminazione', Icons.wb_sunny_outlined),
    ('rifiuti', 'Rifiuti abbandonati', Icons.delete_outline_rounded),
    ('verde', 'Verde pubblico', Icons.park_outlined),
    ('arredo', 'Arredo urbano', Icons.chair_outlined),
    ('animali', 'Animali / igiene', Icons.pets_outlined),
    ('perdite', 'Perdite idriche', Icons.warning_amber_outlined),
    ('altro', 'Altro', Icons.add_circle_outline),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSizes.padX),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 8),
          Text('PASSO 1 DI 4',
              style: theme.textTheme.labelSmall?.copyWith(letterSpacing: 1.2)),
          const SizedBox(height: 6),
          Text('Cosa vuoi segnalare?', style: theme.textTheme.displaySmall),
          const SizedBox(height: 4),
          Text('Tocca la categoria più vicina al problema.',
              style: theme.textTheme.bodyMedium?.copyWith(color: colors.textMuted)),
          const SizedBox(height: 20),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.25,
            children: _categories.map((c) {
              return _CatTile(
                icon: c.$3,
                label: c.$2,
                colors: colors,
                onTap: () => onSelect(c.$1),
              );
            }).toList(),
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _CatTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final AppColorTokens colors;
  final VoidCallback onTap;

  const _CatTile({
    required this.icon,
    required this.label,
    required this.colors,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Semantics(
      button: true,
      label: label,
      child: Material(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          child: Container(
            decoration: BoxDecoration(
              border: Border.all(color: colors.border),
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
            ),
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: colors.primarySoft,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, size: 22, color: colors.primary),
                ),
                const SizedBox(height: 10),
                Text(
                  label,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ── Step 2: Media ─────────────────────────────────────────────────────────────

class _Step2 extends StatelessWidget {
  final AppColorTokens colors;
  final SegnalazioneFormState form;
  final bool recording;
  final int recordingSeconds;
  final VoidCallback onAddPhoto;
  final VoidCallback onAddGallery;
  final ValueChanged<int> onRemoveFoto;
  final VoidCallback onToggleRecording;
  final VoidCallback onContinue;

  const _Step2({
    super.key,
    required this.colors,
    required this.form,
    required this.recording,
    required this.recordingSeconds,
    required this.onAddPhoto,
    required this.onAddGallery,
    required this.onRemoveFoto,
    required this.onToggleRecording,
    required this.onContinue,
  });

  String _formatTimer(int s) {
    final m = s ~/ 60;
    final sec = s % 60;
    return '${m.toString().padLeft(2, '0')}:${sec.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSizes.padX),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 8),
          Text('PASSO 2 DI 4',
              style: theme.textTheme.labelSmall?.copyWith(letterSpacing: 1.2)),
          const SizedBox(height: 6),
          Text('Aggiungi prove', style: theme.textTheme.displaySmall),
          const SizedBox(height: 4),
          Text('Anche solo una foto è sufficiente. Tutto è opzionale.',
              style: theme.textTheme.bodyMedium?.copyWith(color: colors.textMuted)),
          const SizedBox(height: 20),
          // 2×2 media tiles
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.5,
            children: [
              _MediaTile(
                icon: Icons.camera_alt_outlined,
                label: 'Foto',
                subtitle: 'Tocca per\naggiungere',
                iconBg: colors.primarySoft,
                iconColor: colors.primary,
                onTap: onAddPhoto,
              ),
              _MediaTile(
                icon: Icons.videocam_outlined,
                label: 'Video',
                subtitle: 'Tocca per\naggiungere',
                iconBg: colors.dangerSoft,
                iconColor: colors.danger,
                onTap: onAddGallery,
              ),
              _MediaTile(
                icon: Icons.mic_outlined,
                label: 'Voce',
                subtitle: recording ? 'Tocca per\nfermare' : 'Tocca per\naggiungere',
                iconBg: Color(0xFFDCF6E8),
                iconColor: Color(0xFF1ED27A),
                onTap: onToggleRecording,
                isActive: recording,
              ),
              _MediaTile(
                icon: Icons.description_outlined,
                label: 'Solo testo',
                subtitle: 'Tocca per\naggiungere',
                iconBg: colors.warnSoft,
                iconColor: colors.warn,
                onTap: () {},
              ),
            ],
          ),
          // Recording indicator
          if (recording) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: colors.dangerSoft,
                borderRadius: BorderRadius.circular(AppSizes.radiusMd),
                border: Border.all(color: colors.danger.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: colors.danger,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Registrazione — ${_formatTimer(recordingSeconds)}',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: colors.danger,
                    ),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: onToggleRecording,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: colors.danger,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Text(
                        'Salva',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          // Photo thumbnails
          if (form.foto.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              'ALLEGATI (${form.foto.length})',
              style: theme.textTheme.labelSmall?.copyWith(letterSpacing: 1.2),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 10,
              children: List.generate(form.foto.length, (i) {
                return Stack(
                  children: [
                    Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        image: DecorationImage(
                          image: FileImage(form.foto[i]),
                          fit: BoxFit.cover,
                        ),
                        border: Border.all(color: colors.border),
                      ),
                    ),
                    Positioned(
                      top: 2,
                      right: 2,
                      child: GestureDetector(
                        onTap: () => onRemoveFoto(i),
                        child: Container(
                          width: 20,
                          height: 20,
                          decoration: BoxDecoration(
                            color: Colors.black54,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.close, size: 12, color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                );
              }),
            ),
          ],
          const SizedBox(height: 24),
          PrimaryButton(label: 'Continua', onPressed: onContinue),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _MediaTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String subtitle;
  final Color iconBg;
  final Color iconColor;
  final VoidCallback onTap;
  final bool isActive;

  const _MediaTile({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.iconBg,
    required this.iconColor,
    required this.onTap,
    this.isActive = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Semantics(
      button: true,
      label: label,
      child: Material(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          child: Container(
            decoration: BoxDecoration(
              border: Border.all(
                color: isActive ? iconColor : theme.colorScheme.outline,
                width: isActive ? 1.5 : 1,
              ),
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
            ),
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: iconBg,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, size: 20, color: iconColor),
                ),
                const SizedBox(height: 8),
                Text(label,
                    style: theme.textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700)),
                Text(subtitle,
                    style: theme.textTheme.bodySmall,
                    maxLines: 2),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ── Step 3: Location + Description ───────────────────────────────────────────

class _Step3 extends StatelessWidget {
  final AppColorTokens colors;
  final SegnalazioneFormState form;
  final TextEditingController descController;
  final bool detectingLocation;
  final VoidCallback onDetectLocation;
  final VoidCallback onSubmit;

  const _Step3({
    super.key,
    required this.colors,
    required this.form,
    required this.descController,
    required this.detectingLocation,
    required this.onDetectLocation,
    required this.onSubmit,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSizes.padX),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 8),
          Text('PASSO 3 DI 4',
              style: theme.textTheme.labelSmall?.copyWith(letterSpacing: 1.2)),
          const SizedBox(height: 6),
          Text('Dove e cosa', style: theme.textTheme.displaySmall),
          const SizedBox(height: 20),
          // Map preview
          Container(
            height: 140,
            decoration: BoxDecoration(
              color: const Color(0xFFE8F5E9),
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
              border: Border.all(color: colors.border),
            ),
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Simple road SVG-like decoration
                CustomPaint(painter: _RoadPainter(colors.border)),
                Icon(Icons.location_on_rounded,
                    size: 36, color: colors.danger),
              ],
            ),
          ),
          const SizedBox(height: 16),
          // Position field
          _LocationField(
            form: form,
            colors: colors,
            detecting: detectingLocation,
            onDetect: onDetectLocation,
          ),
          const SizedBox(height: 16),
          // Description
          Text('Descrizione (opzionale)', style: theme.textTheme.titleMedium),
          const SizedBox(height: 8),
          TextField(
            controller: descController,
            maxLines: 4,
            decoration: InputDecoration(
              hintText: 'Es. Buca larga circa 30 cm sul lato destro\ndella carreggiata…',
              hintStyle: TextStyle(color: colors.textFaint, fontSize: 14),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Quanto più dettagli aggiungi, più velocemente possiamo intervenire.',
            style: theme.textTheme.bodySmall,
          ),
          const SizedBox(height: 24),
          PrimaryButton(label: 'Rivedi e invia', onPressed: onSubmit),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _LocationField extends StatelessWidget {
  final SegnalazioneFormState form;
  final AppColorTokens colors;
  final bool detecting;
  final VoidCallback onDetect;

  const _LocationField({
    required this.form,
    required this.colors,
    required this.detecting,
    required this.onDetect,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(
          color: form.locationDetected ? colors.accent : colors.border,
          width: form.locationDetected ? 1.5 : 1,
        ),
      ),
      child: Row(
        children: [
          Icon(Icons.location_on_outlined, size: 18, color: colors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: detecting
                ? Text('Rilevamento in corso…',
                    style: TextStyle(color: colors.textMuted))
                : Text(
                    form.indirizzo ?? 'Tocca per rilevare la posizione',
                    style: TextStyle(
                      color: form.indirizzo != null
                          ? colors.text
                          : colors.textFaint,
                    ),
                  ),
          ),
          if (form.locationDetected)
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.check_rounded, size: 16, color: colors.accent),
                const SizedBox(width: 4),
                Text('rilevata',
                    style: TextStyle(
                        color: colors.accent,
                        fontWeight: FontWeight.w600,
                        fontSize: 13)),
              ],
            )
          else if (!detecting)
            TextButton(
              onPressed: onDetect,
              style: TextButton.styleFrom(
                padding: EdgeInsets.zero,
                minimumSize: const Size(60, 32),
              ),
              child: const Text('Rileva'),
            ),
        ],
      ),
    );
  }
}

// ── Step 4: Review ────────────────────────────────────────────────────────────

class _Step4Review extends StatelessWidget {
  final AppColorTokens colors;
  final SegnalazioneFormState form;
  final String descrizione;
  final bool submitting;
  final VoidCallback onSubmit;
  final VoidCallback onEdit;

  const _Step4Review({
    super.key,
    required this.colors,
    required this.form,
    required this.descrizione,
    required this.submitting,
    required this.onSubmit,
    required this.onEdit,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSizes.padX),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 8),
          Text('PASSO 4 DI 4',
              style: theme.textTheme.labelSmall?.copyWith(letterSpacing: 1.2)),
          const SizedBox(height: 6),
          Text('Riepilogo', style: theme.textTheme.displaySmall),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.all(AppSizes.cardPad),
            decoration: BoxDecoration(
              color: colors.bgElev,
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
              border: Border.all(color: colors.border),
            ),
            child: Column(
              children: [
                _ReviewRow(label: 'Categoria', value: form.categoria ?? '—', colors: colors),
                Divider(color: colors.border, height: 20),
                _ReviewRow(label: 'Posizione', value: form.indirizzo ?? 'Non specificata', colors: colors),
                Divider(color: colors.border, height: 20),
                _ReviewRow(label: 'Descrizione', value: descrizione.isEmpty ? '—' : descrizione, colors: colors),
                Divider(color: colors.border, height: 20),
                _ReviewRow(label: 'Foto', value: '${form.foto.length} allegat${form.foto.length == 1 ? 'a' : 'e'}', colors: colors),
              ],
            ),
          ),
          const SizedBox(height: 16),
          TextButton.icon(
            onPressed: onEdit,
            icon: const Icon(Icons.edit_outlined, size: 16),
            label: const Text('Modifica'),
          ),
          const SizedBox(height: 12),
          PrimaryButton(
            label: 'Invia segnalazione',
            onPressed: onSubmit,
            loading: submitting,
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _ReviewRow extends StatelessWidget {
  final String label;
  final String value;
  final AppColorTokens colors;

  const _ReviewRow({required this.label, required this.value, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 100,
          child: Text(label,
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: colors.textMuted)),
        ),
        Expanded(
          child: Text(value,
              style:
                  theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500)),
        ),
      ],
    );
  }
}

// ── Success ───────────────────────────────────────────────────────────────────

class _SuccessStep extends StatelessWidget {
  final AppColorTokens colors;

  const _SuccessStep({super.key, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final code = 'ACN-${DateTime.now().millisecondsSinceEpoch % 100000}';

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(AppSizes.padX),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(
                color: colors.accentSoft,
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.check_rounded, size: 56, color: colors.accent),
            ),
            const SizedBox(height: 24),
            Text('Segnalazione inviata!', style: theme.textTheme.headlineMedium),
            const SizedBox(height: 8),
            Text(
              'La tua segnalazione è stata ricevuta. Ti aggiorneremo sullo stato.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(color: colors.textMuted),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: colors.chip,
                borderRadius: BorderRadius.circular(AppSizes.radiusMd),
              ),
              child: Column(
                children: [
                  Text('Numero pratica',
                      style: theme.textTheme.bodySmall),
                  const SizedBox(height: 4),
                  Text(code,
                      style: theme.textTheme.titleLarge
                          ?.copyWith(color: colors.primary, fontWeight: FontWeight.w700)),
                ],
              ),
            ),
            const SizedBox(height: 40),
            PrimaryButton(
              label: 'Torna alla home',
              onPressed: () => context.go('/home'),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Road painter (map placeholder) ───────────────────────────────────────────

class _RoadPainter extends CustomPainter {
  final Color color;
  _RoadPainter(this.color);

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 8
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    final path = Path()
      ..moveTo(0, size.height * 0.5)
      ..cubicTo(
        size.width * 0.3, size.height * 0.3,
        size.width * 0.7, size.height * 0.7,
        size.width, size.height * 0.4,
      );
    canvas.drawPath(path, paint);

    final path2 = Path()
      ..moveTo(0, size.height * 0.8)
      ..cubicTo(
        size.width * 0.2, size.height * 0.6,
        size.width * 0.6, size.height * 0.9,
        size.width, size.height * 0.6,
      );
    canvas.drawPath(path2, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
