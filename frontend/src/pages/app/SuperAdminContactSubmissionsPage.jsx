import React, { useState, useEffect } from 'react';
import { 
  FiMail, 
  FiUser, 
  FiMessageSquare, 
  FiCalendar,
  FiSearch,
  FiFilter,
  FiEye,
  FiTrash,
  FiRotateCw,
  FiDownload,
  FiCheckCircle,
  FiXCircle,
  FiPhone,
  FiMapPin,
  FiHome,
  FiClock,
  FiAlertCircle,
  FiStar,
  FiArchive
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import { superAdminAPI } from '../../services/api';

const SuperAdminContactSubmissionsPage = () => {
  const { user } = useAuth();
  const [submissions, setSubmissions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedSubmission, setSelectedSubmission] = useState(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  useEffect(() => {
    fetchContactSubmissions();
  }, []);

  const fetchContactSubmissions = async () => {
    try {
      setLoading(true);
      setError(null);

      // Fetch contact submissions from API
      const response = await superAdminAPI.getContactSubmissions();
      
      if (response.data?.success) {
        // Ensure we have the correct data structure
        const submissions = response.data.data?.submissions || response.data.data || [];
        setSubmissions(Array.isArray(submissions) ? submissions : []);
      } else {
        // Use demo data if API fails
        setSubmissions([
          {
            id: 1,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe@example.com',
            phone: '+233 20 123 4567',
            company: 'Tech Solutions Ltd',
            subject: 'Inquiry about Enterprise Plan',
            message: 'I am interested in learning more about your enterprise plan features and pricing. Could you please provide more details about the advanced analytics and reporting capabilities?',
            status: 'new',
            ip_address: '192.168.1.100',
            user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            created_at: '2024-01-15T10:30:00Z',
            updated_at: '2024-01-15T10:30:00Z'
          },
          {
            id: 2,
            first_name: 'Sarah',
            last_name: 'Johnson',
            email: 'sarah.johnson@retail.com',
            phone: '+233 24 987 6543',
            company: 'Retail Store Chain',
            subject: 'POS System Demo Request',
            message: 'We are looking to upgrade our POS system and would like to schedule a demo of your solution. We have 5 locations and need a system that can handle multiple stores.',
            status: 'in_progress',
            ip_address: '203.0.113.45',
            user_agent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/537.36',
            created_at: '2024-01-14T15:45:00Z',
            updated_at: '2024-01-15T09:20:00Z'
          },
          {
            id: 3,
            first_name: 'Michael',
            last_name: 'Chen',
            email: 'michael.chen@restaurant.com',
            phone: '+233 26 555 1234',
            company: 'Golden Dragon Restaurant',
            subject: 'Pricing Information',
            message: 'Could you please send me pricing information for your POS system? We are a small restaurant with about 20 tables and need something affordable but reliable.',
            status: 'completed',
            ip_address: '198.51.100.10',
            user_agent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
            created_at: '2024-01-13T11:20:00Z',
            updated_at: '2024-01-14T16:30:00Z'
          }
        ]);
      }
    } catch (error) {
      console.error('Error fetching contact submissions:', error);
      setError('Failed to load contact submissions. Using demo data.');
      
      // Use demo data as fallback
      setSubmissions([
        {
          id: 1,
          first_name: 'John',
          last_name: 'Doe',
          email: 'john.doe@example.com',
          phone: '+233 20 123 4567',
          company: 'Tech Solutions Ltd',
          subject: 'Inquiry about Enterprise Plan',
          message: 'I am interested in learning more about your enterprise plan features and pricing.',
          status: 'new',
          ip_address: '192.168.1.100',
          user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          created_at: '2024-01-15T10:30:00Z',
          updated_at: '2024-01-15T10:30:00Z'
        }
      ]);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'new': return 'text-blue-600 bg-blue-100';
      case 'in_progress': return 'text-yellow-600 bg-yellow-100';
      case 'completed': return 'text-green-600 bg-green-100';
      case 'spam': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'new': return <FiAlertCircle className="w-4 h-4" />;
      case 'in_progress': return <FiClock className="w-4 h-4" />;
      case 'completed': return <FiCheckCircle className="w-4 h-4" />;
      case 'spam': return <FiXCircle className="w-4 h-4" />;
      default: return <FiMail className="w-4 h-4" />;
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  const handleStatusChange = async (submissionId, newStatus) => {
    try {
      // Update status via API
      await superAdminAPI.updateContactSubmission(submissionId, { status: newStatus });
      
      // Update local state
      setSubmissions(prev => 
        prev.map(sub => 
          sub.id === submissionId 
            ? { ...sub, status: newStatus, updated_at: new Date().toISOString() }
            : sub
        )
      );
    } catch (err) {
      console.error('Error updating submission status:', err);
    }
  };

  const handleDelete = async (submissionId) => {
    if (window.confirm('Are you sure you want to delete this submission?')) {
      try {
        await superAdminAPI.deleteContactSubmission(submissionId);
        setSubmissions(prev => prev.filter(sub => sub.id !== submissionId));
      } catch (err) {
        console.error('Error deleting submission:', err);
      }
    }
  };

  const filteredSubmissions = () => {
    // Ensure submissions is an array
    if (!Array.isArray(submissions)) {
      return [];
    }

    let filtered = submissions;
    
    if (searchTerm) {
      filtered = filtered.filter(submission => 
        submission.first_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        submission.last_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        submission.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        submission.subject?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        submission.message?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (filter !== 'all') {
      filtered = filtered.filter(submission => submission.status === filter);
    }

    return filtered;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/4 mb-6"></div>
            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
              {[1, 2, 3, 4].map(i => (
                <div key={i} className="bg-white rounded-lg shadow p-6">
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                  <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                <FiMail className="mr-3 text-primary" />
                Contact Submissions
              </h1>
              <p className="text-gray-600 mt-2">
                Manage and respond to contact form submissions
              </p>
            </div>
            <div className="flex items-center space-x-4">
              <button
                onClick={fetchContactSubmissions}
                className="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center transition-colors"
              >
                <FiRotateCw className="mr-2" />
                Refresh
              </button>
              <button
                className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
              >
                <FiDownload className="mr-2" />
                Export
              </button>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-blue-100 rounded-lg">
                <FiMail className="text-blue-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Submissions</p>
                <p className="text-2xl font-bold text-gray-900">
                  {submissions.length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FiAlertCircle className="text-yellow-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">New</p>
                <p className="text-2xl font-bold text-gray-900">
                  {submissions.filter(s => s.status === 'new').length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-green-100 rounded-lg">
                <FiCheckCircle className="text-green-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Completed</p>
                <p className="text-2xl font-bold text-gray-900">
                  {submissions.filter(s => s.status === 'completed').length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-red-100 rounded-lg">
                <FiXCircle className="text-red-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Spam</p>
                <p className="text-2xl font-bold text-gray-900">
                  {submissions.filter(s => s.status === 'spam').length}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters and Search */}
        <div className="bg-white rounded-lg shadow mb-6 p-6">
          <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div className="flex gap-4">
              <select
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
                className="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="all">All Submissions</option>
                <option value="new">New</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="spam">Spam</option>
              </select>
            </div>
            
            <div className="relative">
              <input
                type="text"
                placeholder="Search submissions..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-64 focus:ring-2 focus:ring-primary focus:border-transparent"
              />
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            </div>
          </div>
        </div>

        {/* Submissions Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Contact Form Submissions</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Contact
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Company
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Subject
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredSubmissions().map((submission) => (
                  <tr key={submission.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="h-10 w-10 rounded-full bg-primary flex items-center justify-center">
                          <span className="text-sm font-medium text-white">
                            {submission.first_name[0]}{submission.last_name[0]}
                          </span>
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {submission.first_name} {submission.last_name}
                          </div>
                          <div className="text-sm text-gray-500">
                            {submission.email}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm text-gray-900">
                        {submission.company || 'N/A'}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm text-gray-900 max-w-xs truncate">
                        {submission.subject}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <select
                        value={submission.status}
                        onChange={(e) => handleStatusChange(submission.id, e.target.value)}
                        className={`text-xs px-2 py-1 rounded-full border-0 focus:ring-2 focus:ring-primary ${getStatusColor(submission.status)}`}
                      >
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="spam">Spam</option>
                      </select>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {formatDate(submission.created_at)}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => {
                            setSelectedSubmission(submission);
                            setShowDetailModal(true);
                          }}
                          className="text-blue-400 hover:text-blue-600"
                          title="View Details"
                        >
                          <FiEye size={14} />
                        </button>
                        <button
                          onClick={() => handleDelete(submission.id)}
                          className="text-red-400 hover:text-red-600"
                          title="Delete"
                        >
                          <FiTrash size={14} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Detail Modal */}
        {showDetailModal && selectedSubmission && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-semibold text-gray-900">
                    Submission Details
                  </h2>
                  <button
                    onClick={() => setShowDetailModal(false)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <FiXCircle size={24} />
                  </button>
                </div>

                <div className="space-y-6">
                  {/* Contact Information */}
                  <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Name</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {selectedSubmission.first_name} {selectedSubmission.last_name}
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Email</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {selectedSubmission.email}
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Phone</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {selectedSubmission.phone || 'N/A'}
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Company</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {selectedSubmission.company || 'N/A'}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Message Details */}
                  <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Message</h3>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Subject</label>
                      <p className="text-sm text-gray-900 mt-1">
                        {selectedSubmission.subject}
                      </p>
                    </div>
                    <div className="mt-4">
                      <label className="block text-sm font-medium text-gray-700">Message</label>
                      <div className="mt-1 p-3 bg-gray-50 rounded-lg">
                        <p className="text-sm text-gray-900 whitespace-pre-wrap">
                          {selectedSubmission.message}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Technical Details */}
                  <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Technical Details</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">IP Address</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {selectedSubmission.ip_address}
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Status</label>
                        <select
                          value={selectedSubmission.status}
                          onChange={(e) => handleStatusChange(selectedSubmission.id, e.target.value)}
                          className={`mt-1 text-sm px-3 py-2 rounded-lg border focus:ring-2 focus:ring-primary ${getStatusColor(selectedSubmission.status)}`}
                        >
                          <option value="new">New</option>
                          <option value="in_progress">In Progress</option>
                          <option value="completed">Completed</option>
                          <option value="spam">Spam</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Submitted</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {formatDate(selectedSubmission.created_at)}
                        </p>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Last Updated</label>
                        <p className="text-sm text-gray-900 mt-1">
                          {formatDate(selectedSubmission.updated_at)}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex justify-end space-x-3 pt-4 border-t">
                    <button
                      onClick={() => setShowDetailModal(false)}
                      className="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                      Close
                    </button>
                    <button
                      onClick={() => {
                        // Handle reply functionality
                        console.log('Reply to:', selectedSubmission.email);
                      }}
                      className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
                    >
                      Reply
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SuperAdminContactSubmissionsPage;
