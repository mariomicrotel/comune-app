import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/constants/app_sizes.dart';

class ComuneBottomNavigation extends StatelessWidget {
  final StatefulNavigationShell navigationShell;

  const ComuneBottomNavigation({super.key, required this.navigationShell});

  static const _tabs = [
    _NavTab(icon: Icons.home_outlined, activeIcon: Icons.home_rounded, label: 'Home', path: '/home'),
    _NavTab(icon: Icons.campaign_outlined, activeIcon: Icons.campaign_rounded, label: 'Avvisi', path: '/avvisi'),
    _NavTab(icon: Icons.add, activeIcon: Icons.add, label: 'Segnala', path: '/segnala', isFab: true),
    _NavTab(icon: Icons.map_outlined, activeIcon: Icons.map_rounded, label: 'Mappa', path: '/luoghi/mappa'),
    _NavTab(icon: Icons.person_outline_rounded, activeIcon: Icons.person_rounded, label: 'Profilo', path: '/profilo'),
  ];

  void _onTap(BuildContext context, int index) {
    if (_tabs[index].isFab) {
      context.push('/segnala/new');
      return;
    }
    // Map to shell branch index (FAB is not a branch)
    final branchIndex = index > 2 ? index - 1 : index;
    navigationShell.goBranch(
      branchIndex,
      initialLocation: branchIndex == navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    final muted = theme.colorScheme.onSurfaceVariant;

    // Current branch → highlight correct tab (skip FAB index 2)
    int activeTab = navigationShell.currentIndex;
    if (activeTab >= 2) activeTab += 1; // shift past FAB

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface.withValues(alpha: 0.95),
        border: Border(top: BorderSide(color: theme.colorScheme.outline, width: 0.5)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: AppSizes.bottomNavHeight,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Row(
                children: List.generate(_tabs.length, (i) {
                  if (_tabs[i].isFab) {
                    // Placeholder space for FAB
                    return const Expanded(child: SizedBox());
                  }
                  final active = i == activeTab;
                  return Expanded(
                    child: InkWell(
                      onTap: () => _onTap(context, i),
                      child: Semantics(
                        label: _tabs[i].label,
                        selected: active,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              active ? _tabs[i].activeIcon : _tabs[i].icon,
                              size: 24,
                              color: active ? primary : muted,
                            ),
                            const SizedBox(height: 3),
                            Text(
                              _tabs[i].label,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: active ? FontWeight.w600 : FontWeight.w400,
                                color: active ? primary : muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }),
              ),
              // Elevated FAB
              Positioned(
                top: AppSizes.fabOffset,
                left: 0,
                right: 0,
                child: Center(
                  child: Semantics(
                    label: 'Segnala un problema',
                    button: true,
                    child: GestureDetector(
                      onTap: () => _onTap(context, 2),
                      child: Container(
                        width: AppSizes.fabSize,
                        height: AppSizes.fabSize,
                        decoration: BoxDecoration(
                          color: primary,
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: [
                            BoxShadow(
                              color: primary.withValues(alpha: 0.40),
                              blurRadius: 16,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.add,
                          color: Colors.white,
                          size: 28,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavTab {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final String path;
  final bool isFab;

  const _NavTab({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.path,
    this.isFab = false,
  });
}
