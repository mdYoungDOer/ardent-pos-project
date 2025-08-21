import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { HiPlus, HiPencil, HiTrash, HiSearch, HiMail, HiPhone } from 'react-icons/hi'
import api from '../../services/api'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

const CustomersPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [showAddModal, setShowAddModal] = useState(false)
  const queryClient = useQueryClient()

  const { data: customers, isLoading } = useQuery(
    ['customers', searchTerm],
    () => api.get('/customers', {
      params: { search: searchTerm }
    }).then(res => res.data)
  )

  const deleteCustomer = useMutation(
    (id) => api.delete(`/customers/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('customers')
        toast.success('Customer deleted successfully')
      },
      onError: () => {
        toast.error('Failed to delete customer')
      }
    }
  )

  const handleDelete = (customer) => {
    if (window.confirm(`Are you sure you want to delete "${customer.first_name} ${customer.last_name}"?`)) {
      deleteCustomer.mutate(customer.id)
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Customers</h1>
          <p className="text-gray-600">Manage your customer database</p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowAddModal(true)}
            className="btn-primary flex items-center"
          >
            <HiPlus className="h-5 w-5 mr-2" />
            Add Customer
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="bg-white shadow-sm rounded-lg p-6">
        <div className="relative max-w-md">
          <HiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search customers..."
            className="form-input pl-10"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Customers Grid */}
      <div className="bg-white shadow-sm rounded-lg overflow-hidden">
        {customers?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="table">
              <thead className="table-header">
                <tr>
                  <th>Customer</th>
                  <th>Contact</th>
                  <th>Location</th>
                  <th>Total Spent</th>
                  <th>Loyalty Points</th>
                  <th>Last Purchase</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody className="table-body">
                {customers.map((customer) => (
                  <tr key={customer.id}>
                    <td>
                      <div className="flex items-center">
                        <div className="h-10 w-10 flex-shrink-0">
                          <div className="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                            <span className="text-sm font-medium">
                              {customer.first_name.charAt(0)}{customer.last_name.charAt(0)}
                            </span>
                          </div>
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {customer.first_name} {customer.last_name}
                          </div>
                          <div className="text-sm text-gray-500">
                            Customer since {new Date(customer.created_at).getFullYear()}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div className="space-y-1">
                        {customer.email && (
                          <div className="flex items-center text-sm text-gray-900">
                            <HiMail className="h-4 w-4 mr-2 text-gray-400" />
                            {customer.email}
                          </div>
                        )}
                        {customer.phone && (
                          <div className="flex items-center text-sm text-gray-900">
                            <HiPhone className="h-4 w-4 mr-2 text-gray-400" />
                            {customer.phone}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="text-sm text-gray-900">
                      {customer.city && customer.state 
                        ? `${customer.city}, ${customer.state}`
                        : customer.city || customer.state || '-'
                      }
                    </td>
                    <td className="text-sm font-medium text-gray-900">
                      ₦{parseFloat(customer.total_spent || 0).toLocaleString()}
                    </td>
                    <td>
                      <span className="badge badge-info">
                        {customer.loyalty_points || 0} pts
                      </span>
                    </td>
                    <td className="text-sm text-gray-500">
                      {customer.last_purchase 
                        ? new Date(customer.last_purchase).toLocaleDateString()
                        : 'Never'
                      }
                    </td>
                    <td>
                      <div className="flex items-center space-x-2">
                        <button
                          className="text-primary hover:text-primary-600"
                          title="Edit customer"
                        >
                          <HiPencil className="h-5 w-5" />
                        </button>
                        <button
                          onClick={() => handleDelete(customer)}
                          className="text-red-600 hover:text-red-700"
                          title="Delete customer"
                        >
                          <HiTrash className="h-5 w-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <div className="text-gray-400 text-6xl mb-4">👥</div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
            <p className="text-gray-500 mb-6">
              {searchTerm 
                ? 'Try adjusting your search criteria'
                : 'Start building your customer database'
              }
            </p>
            <button
              onClick={() => setShowAddModal(true)}
              className="btn-primary"
            >
              Add Customer
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

export default CustomersPage
