import 'package:flutter/material.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_sizes.dart';

/// A tile representing a downloadable/openable file attachment.
class AttachmentTile extends StatelessWidget {
  final String nome;
  final String? url;
  final int? dimensioneKb;
  final AppColorTokens colors;
  final VoidCallback? onTap;

  const AttachmentTile({
    super.key,
    required this.nome,
    this.url,
    this.dimensioneKb,
    required this.colors,
    this.onTap,
  });

  String get _ext {
    final parts = nome.split('.');
    return parts.length > 1 ? parts.last.toUpperCase() : 'FILE';
  }

  String get _size {
    if (dimensioneKb == null) return '';
    if (dimensioneKb! >= 1024) {
      return '${(dimensioneKb! / 1024).toStringAsFixed(1)} MB';
    }
    return '$dimensioneKb KB';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSizes.padX, vertical: 12),
        decoration: BoxDecoration(
          color: colors.bgElev,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          border: Border.all(color: colors.border),
        ),
        child: Row(
          children: [
            // File icon
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: colors.dangerSoft,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Center(
                child: Text(
                  _ext,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: colors.danger,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    nome,
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (_size.isNotEmpty)
                    Text(
                      _size,
                      style: theme.textTheme.bodySmall
                          ?.copyWith(color: colors.textFaint),
                    ),
                ],
              ),
            ),
            Icon(
              Icons.download_outlined,
              size: 20,
              color: colors.primary,
            ),
          ],
        ),
      ),
    );
  }
}
