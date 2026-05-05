import 'package:flutter/material.dart';
import '../models/avviso.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_sizes.dart';

class PriorityBadge extends StatelessWidget {
  final PrioritaAvviso priorita;
  final AppColorTokens colors;

  const PriorityBadge({
    super.key,
    required this.priorita,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final (bg, fg, dot) = _palette();

    return Semantics(
      label: 'Priorità: ${priorita.label}',
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppSizes.radiusFull),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(
                color: dot,
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 5),
            Text(
              priorita.label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: fg,
              ),
            ),
          ],
        ),
      ),
    );
  }

  (Color bg, Color fg, Color dot) _palette() {
    switch (priorita) {
      case PrioritaAvviso.urgente:
      case PrioritaAvviso.alta:
        return (colors.dangerSoft, colors.danger, colors.danger);
      case PrioritaAvviso.media:
        return (colors.warnSoft, colors.warn, colors.warn);
      case PrioritaAvviso.bassa:
        return (colors.primarySoft, colors.primary, colors.primary);
    }
  }
}
