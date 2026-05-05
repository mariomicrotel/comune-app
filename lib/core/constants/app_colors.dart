import 'package:flutter/material.dart';

enum AppPalette { bluCivico, bluSavoia, verdeBorgo, tricolore }

class AppColorTokens {
  final Color primary;
  final Color primaryDeep;
  final Color primarySoft;
  final Color accent;
  final Color accentSoft;
  final Color warn;
  final Color warnSoft;
  final Color danger;
  final Color dangerSoft;
  final Color text;
  final Color textMuted;
  final Color textFaint;
  final Color bg;
  final Color bgElev;
  final Color border;
  final Color borderStrong;
  final Color chip;

  const AppColorTokens({
    required this.primary,
    required this.primaryDeep,
    required this.primarySoft,
    required this.accent,
    required this.accentSoft,
    required this.warn,
    required this.warnSoft,
    required this.danger,
    required this.dangerSoft,
    required this.text,
    required this.textMuted,
    required this.textFaint,
    required this.bg,
    required this.bgElev,
    required this.border,
    required this.borderStrong,
    required this.chip,
  });
}

class AppColors {
  AppColors._();

  static const Map<AppPalette, AppColorTokens> light = {
    AppPalette.bluCivico: AppColorTokens(
      primary: Color(0xFF0B5FFF),
      primaryDeep: Color(0xFF0A4ACF),
      primarySoft: Color(0xFFE5EEFF),
      accent: Color(0xFF1ED27A),
      accentSoft: Color(0xFFDCF6E8),
      warn: Color(0xFFE8A317),
      warnSoft: Color(0xFFFFF4DA),
      danger: Color(0xFFD7263D),
      dangerSoft: Color(0xFFFBE3E6),
      text: Color(0xFF0B1B2B),
      textMuted: Color(0xFF5A6B7C),
      textFaint: Color(0xFF8A98A6),
      bg: Color(0xFFF4F6FA),
      bgElev: Color(0xFFFFFFFF),
      border: Color(0xFFE2E8F0),
      borderStrong: Color(0xFFCBD5E1),
      chip: Color(0xFFEEF2F7),
    ),
    AppPalette.bluSavoia: AppColorTokens(
      primary: Color(0xFF0A4A8A),
      primaryDeep: Color(0xFF06335E),
      primarySoft: Color(0xFFE3ECF6),
      accent: Color(0xFFD4A23A),
      accentSoft: Color(0xFFF8EFD6),
      warn: Color(0xFFC97A1A),
      warnSoft: Color(0xFFFBE9CF),
      danger: Color(0xFFB0202E),
      dangerSoft: Color(0xFFF6DDE0),
      text: Color(0xFF0E1B2A),
      textMuted: Color(0xFF56697C),
      textFaint: Color(0xFF8A99A8),
      bg: Color(0xFFF4F1EA),
      bgElev: Color(0xFFFFFFFF),
      border: Color(0xFFE5DFD3),
      borderStrong: Color(0xFFCFC6B4),
      chip: Color(0xFFEFEAD9),
    ),
    AppPalette.verdeBorgo: AppColorTokens(
      primary: Color(0xFF1B4332),
      primaryDeep: Color(0xFF0E2C20),
      primarySoft: Color(0xFFDBEAE2),
      accent: Color(0xFFC9A227),
      accentSoft: Color(0xFFF4EAC2),
      warn: Color(0xFFB7791F),
      warnSoft: Color(0xFFF6E6BE),
      danger: Color(0xFF9C2A2A),
      dangerSoft: Color(0xFFF0D9D9),
      text: Color(0xFF0F1F18),
      textMuted: Color(0xFF4F5E55),
      textFaint: Color(0xFF8A968F),
      bg: Color(0xFFF8F6F1),
      bgElev: Color(0xFFFFFFFF),
      border: Color(0xFFE2E0D7),
      borderStrong: Color(0xFFC7C4B6),
      chip: Color(0xFFEDEBE1),
    ),
    AppPalette.tricolore: AppColorTokens(
      primary: Color(0xFF0B3D91),
      primaryDeep: Color(0xFF082A66),
      primarySoft: Color(0xFFDDE5F4),
      accent: Color(0xFF0E8C4A),
      accentSoft: Color(0xFFD7EFE0),
      warn: Color(0xFFD38A1B),
      warnSoft: Color(0xFFFAEACB),
      danger: Color(0xFFC53030),
      dangerSoft: Color(0xFFF6D7D7),
      text: Color(0xFF10182A),
      textMuted: Color(0xFF566073),
      textFaint: Color(0xFF8A95A8),
      bg: Color(0xFFF5F2EC),
      bgElev: Color(0xFFFFFFFF),
      border: Color(0xFFE4DFD4),
      borderStrong: Color(0xFFC9C2B3),
      chip: Color(0xFFECE7DA),
    ),
  };

  static const AppColorTokens dark = AppColorTokens(
    primary: Color(0xFF0B5FFF),
    primaryDeep: Color(0xFF0A4ACF),
    primarySoft: Color(0x260B5FFF),
    accent: Color(0xFF1ED27A),
    accentSoft: Color(0x261ED27A),
    warn: Color(0xFFE8A317),
    warnSoft: Color(0x26E8A317),
    danger: Color(0xFFD7263D),
    dangerSoft: Color(0x26D7263D),
    text: Color(0xFFF1F4FA),
    textMuted: Color(0xFFA4B0C0),
    textFaint: Color(0xFF6F7E91),
    bg: Color(0xFF0B1422),
    bgElev: Color(0xFF13202E),
    border: Color(0xFF1F2F42),
    borderStrong: Color(0xFF2A3D55),
    chip: Color(0xFF1A2839),
  );

  // Waste collection type colors
  static const Color wasteOrganico = Color(0xFF8B6F47);
  static const Color wastePlastica = Color(0xFFFFB72B);
  static const Color wasteCarta = Color(0xFF3F88E0);
  static const Color wasteVetro = Color(0xFF3FA75F);
  static const Color wasteIndifferenziata = Color(0xFF5C6470);

  static Color wasteColor(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'organico':
      case 'umido':
        return wasteOrganico;
      case 'plastica':
      case 'plastica e metalli':
        return wastePlastica;
      case 'carta':
        return wasteCarta;
      case 'vetro':
        return wasteVetro;
      default:
        return wasteIndifferenziata;
    }
  }
}
