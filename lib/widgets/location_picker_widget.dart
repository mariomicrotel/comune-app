import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_sizes.dart';

/// Interactive map widget for picking a location.
/// Shows a draggable marker; calls [onLocationPicked] with the chosen coordinates.
class LocationPickerWidget extends StatefulWidget {
  final LatLng initialCenter;
  final AppColorTokens colors;
  final void Function(LatLng position, String address) onLocationPicked;

  const LocationPickerWidget({
    super.key,
    required this.initialCenter,
    required this.colors,
    required this.onLocationPicked,
  });

  @override
  State<LocationPickerWidget> createState() => _LocationPickerWidgetState();
}

class _LocationPickerWidgetState extends State<LocationPickerWidget> {
  late LatLng _selected;

  @override
  void initState() {
    super.initState();
    _selected = widget.initialCenter;
  }

  void _onTap(TapPosition _, LatLng pos) {
    setState(() => _selected = pos);
    // In production: reverse-geocode pos → address string
    final address =
        '${pos.latitude.toStringAsFixed(5)}, ${pos.longitude.toStringAsFixed(5)}';
    widget.onLocationPicked(pos, address);
  }

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppSizes.radiusMd),
      child: SizedBox(
        height: 200,
        child: FlutterMap(
          options: MapOptions(
            initialCenter: widget.initialCenter,
            initialZoom: 15,
            onTap: _onTap,
          ),
          children: [
            TileLayer(
              urlTemplate:
                  'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              userAgentPackageName: 'it.comune.app',
            ),
            MarkerLayer(
              markers: [
                Marker(
                  point: _selected,
                  width: 40,
                  height: 48,
                  child: Column(
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: widget.colors.primary,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color:
                                  widget.colors.primary.withValues(alpha: 0.35),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: const Icon(Icons.place_rounded,
                            color: Colors.white, size: 20),
                      ),
                      Container(
                        width: 3,
                        height: 8,
                        decoration: BoxDecoration(
                          color: widget.colors.primary,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
