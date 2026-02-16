import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:frontend/services/api_service.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  void _loadUser() async {
    final user = await Provider.of<ApiService>(context, listen: false).getCurrentUser();
    if (mounted) {
      if (user == null) {
        Navigator.pushReplacementNamed(context, '/login');
      } else {
        setState(() => _user = user);
      }
    }
  }

  void _logout() async {
    await Provider.of<ApiService>(context, listen: false).logout();
    if (mounted) {
      Navigator.pushReplacementNamed(context, '/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_user == null) return const Scaffold(body: Center(child: CircularProgressIndicator()));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Library Dashboard'),
        actions: [
          IconButton(icon: const Icon(Icons.logout), onPressed: _logout),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              color: Colors.teal.shade50,
              child: ListTile(
                leading: const CircleAvatar(child: Icon(Icons.person)),
                title: Text('Welcome, ${_user!['name']}'),
                subtitle: Text('Role: ${_user!['role']}'),
              ),
            ),
            const SizedBox(height: 24),
            Expanded(
              child: GridView.count(
                crossAxisCount: 2,
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                children: [
                  _DashboardTile(
                    icon: Icons.book,
                    title: 'Book Catalog',
                    onTap: () => Navigator.pushNamed(context, '/books'),
                  ),
                  _DashboardTile(
                    icon: Icons.swap_horiz,
                    title: 'Issue / Return',
                    onTap: () => Navigator.pushNamed(context, '/issue-return'),
                  ),
                  _DashboardTile(
                    icon: Icons.history,
                    title: 'My History',
                    onTap: () => Navigator.pushNamed(context, '/history'),
                  ),
                  if (_user!['role'] == 'admin')
                    _DashboardTile(
                      icon: Icons.add_circle,
                      title: 'Add Book',
                      onTap: () => Navigator.pushNamed(context, '/add-book'),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DashboardTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;

  const _DashboardTile({
    required this.icon,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 48, color: Colors.teal),
            const SizedBox(height: 16),
            Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}
