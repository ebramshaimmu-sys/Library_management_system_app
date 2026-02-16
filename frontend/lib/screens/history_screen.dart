import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:frontend/services/api_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _history = [];
  bool _isLoading = true;
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  void _loadHistory() async {
    final api = Provider.of<ApiService>(context, listen: false);
    _user = await api.getCurrentUser();
    
    if (_user != null) {
      try {
        final data = await api.getUserHistory(_user!['id']);
        if (mounted) {
          setState(() {
            _history = data;
            _isLoading = false;
          });
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error loading history: $e')),
          );
          setState(() => _isLoading = false);
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My History')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _history.isEmpty
            ? const Center(child: Text('No history found.'))
            : ListView.builder(
                itemCount: _history.length,
                itemBuilder: (context, index) {
                  final item = _history[index];
                  final isReturned = item['status'] == 'returned';
                  return Card(
                    margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: ListTile(
                      leading: Icon(
                        isReturned ? Icons.check_circle : Icons.book,
                        color: isReturned ? Colors.green : Colors.orange,
                      ),
                      title: Text(item['title'], style: const TextStyle(fontWeight: FontWeight.bold)),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Author: ${item['author']}'),
                          Text('Issued: ${item['issue_date']}'),
                          if (item['return_date'] != null)
                             Text('Returned: ${item['return_date']}'),
                          Text('Transaction ID: ${item['id']}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                      isThreeLine: true,
                    ),
                  );
                },
              ),
    );
  }
}
