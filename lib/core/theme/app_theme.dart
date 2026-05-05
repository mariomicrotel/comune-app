import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_sizes.dart';

class AppTheme {
  AppTheme._();

  static ThemeData light({AppPalette palette = AppPalette.bluCivico}) {
    final c = AppColors.light[palette]!;
    return _buildTheme(c, Brightness.light);
  }

  static ThemeData dark({AppPalette palette = AppPalette.bluCivico}) {
    return _buildTheme(AppColors.dark, Brightness.dark);
  }

  static ThemeData _buildTheme(AppColorTokens c, Brightness brightness) {
    final base = brightness == Brightness.light
        ? ThemeData.light(useMaterial3: true)
        : ThemeData.dark(useMaterial3: true);

    final textTheme = GoogleFonts.interTightTextTheme(base.textTheme).copyWith(
      displayLarge: GoogleFonts.fraunces(
        fontSize: 30,
        fontWeight: FontWeight.w700,
        color: c.text,
      ),
      displayMedium: GoogleFonts.fraunces(
        fontSize: 26,
        fontWeight: FontWeight.w700,
        color: c.text,
      ),
      displaySmall: GoogleFonts.fraunces(
        fontSize: 21,
        fontWeight: FontWeight.w600,
        color: c.text,
      ),
      headlineLarge: GoogleFonts.interTight(
        fontSize: 26,
        fontWeight: FontWeight.w700,
        color: c.text,
      ),
      headlineMedium: GoogleFonts.interTight(
        fontSize: 21,
        fontWeight: FontWeight.w600,
        color: c.text,
      ),
      headlineSmall: GoogleFonts.interTight(
        fontSize: 19,
        fontWeight: FontWeight.w600,
        color: c.text,
      ),
      titleLarge: GoogleFonts.interTight(
        fontSize: 17,
        fontWeight: FontWeight.w600,
        color: c.text,
      ),
      titleMedium: GoogleFonts.interTight(
        fontSize: 15,
        fontWeight: FontWeight.w500,
        color: c.text,
      ),
      bodyLarge: GoogleFonts.interTight(
        fontSize: 17,
        fontWeight: FontWeight.w400,
        color: c.text,
      ),
      bodyMedium: GoogleFonts.interTight(
        fontSize: 15,
        fontWeight: FontWeight.w400,
        color: c.text,
      ),
      bodySmall: GoogleFonts.interTight(
        fontSize: 13,
        fontWeight: FontWeight.w400,
        color: c.textMuted,
      ),
      labelLarge: GoogleFonts.interTight(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: c.text,
      ),
      labelSmall: GoogleFonts.interTight(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        color: c.textFaint,
        letterSpacing: 0.5,
      ),
    );

    return base.copyWith(
      brightness: brightness,
      colorScheme: ColorScheme(
        brightness: brightness,
        primary: c.primary,
        onPrimary: Colors.white,
        primaryContainer: c.primarySoft,
        onPrimaryContainer: c.primaryDeep,
        secondary: c.accent,
        onSecondary: Colors.white,
        secondaryContainer: c.accentSoft,
        onSecondaryContainer: c.accent,
        error: c.danger,
        onError: Colors.white,
        errorContainer: c.dangerSoft,
        onErrorContainer: c.danger,
        surface: c.bgElev,
        onSurface: c.text,
        surfaceContainerHighest: c.chip,
        outline: c.border,
        outlineVariant: c.borderStrong,
      ),
      scaffoldBackgroundColor: c.bg,
      textTheme: textTheme,
      cardTheme: CardThemeData(
        color: c.bgElev,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          side: BorderSide(color: c.border),
        ),
        margin: EdgeInsets.zero,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: c.bgElev,
        foregroundColor: c.text,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: c.border,
        titleTextStyle: GoogleFonts.interTight(
          fontSize: 17,
          fontWeight: FontWeight.w600,
          color: c.text,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: c.bgElev,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          borderSide: BorderSide(color: c.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          borderSide: BorderSide(color: c.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          borderSide: BorderSide(color: c.primary, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSizes.padX,
          vertical: AppSizes.padY,
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: c.chip,
        selectedColor: c.primarySoft,
        labelStyle: GoogleFonts.interTight(
          fontSize: 13,
          fontWeight: FontWeight.w500,
          color: c.text,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSizes.radiusFull),
        ),
        side: BorderSide.none,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: c.primary,
          foregroundColor: Colors.white,
          minimumSize: const Size(double.infinity, AppSizes.minTapTarget),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          ),
          textStyle: GoogleFonts.interTight(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: c.primary,
          minimumSize: const Size(double.infinity, AppSizes.minTapTarget),
          side: BorderSide(color: c.primary),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          ),
          textStyle: GoogleFonts.interTight(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      dividerTheme: DividerThemeData(
        color: c.border,
        thickness: 1,
        space: 0,
      ),
      listTileTheme: ListTileThemeData(
        minTileHeight: AppSizes.rowH,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSizes.padX,
          vertical: AppSizes.spacingSm,
        ),
      ),
    );
  }
}
