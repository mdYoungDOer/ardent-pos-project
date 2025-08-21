import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { HiExclamationCircle, HiPencil, HiPlus, HiSearch } from 'react-icons/hi'
import api from '../../services/api'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

const InventoryPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [filterType, setFilterType] = useState('all')
  const queryClient = useQueryClient()

  const { data: inventory, isLoading } = useQuery(
    ['inventory', searchTerm, filterType],
    () => api.get('/inventory', {
      params: { search: searchTerm, filter: filterType }
    }).then(res => res.data)
  )

  const adjustInventory = useMutation(
    ({ productId, adjustment, reason }) => 
      api.post('/inventory/adjust', { product_id: productId, adjustment, reason }),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('inventory')
        toast.success('Inventory adjusted successfully')
      },
      onError: () => {
        toast.error('Failed to adjust inventory')
      }
    }
  )

  const handleAdjustment = (product, adjustment, reason) => {
    if (!reason.trim()) {
      toast.error('Please provide a reason for the adjustment')
      return
    }
    adjustInventory.mutate({ productId: product.id, adjustment, reason })
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
          <h1 className="text-2xl font-bold text-gray-900">Inventory Management</h1>
          <p className="text-gray-600">Track and manage your stock levels</p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white shadow-sm rounded-lg p-6">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <label className="form-label">Search products</label>
            <div className="relative">
              <HiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type="text"
                placeholder="Search by name or SKU..."
                className="form-input pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
          
          <div>
            <label className="form-label">Filter by stock level</label>
            <select
              className="form-input"
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
            >
              <option value="all">All products</option>
              <option value="low_stock">Low stock</option>
              <option value="out_of_stock">Out of stock</option>
              <option value="overstocked">Overstocked</option>
            </select>
          </div>
        </div>
      </div>

      {/* Inventory Table */}
      <div className="bg-white shadow-sm rounded-lg overflow-hidden">
        {inventory?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="table">
              <thead className="table-header">
                <tr>
                  <th>Product</th>
                  <th>SKU</th>
                  <th>Current Stock</th>
                  <th>Min Stock</th>
                  <th>Max Stock</th>
                  <th>Status</th>
                  <th>Last Updated</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody className="table-body">
                {inventory.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <div className="flex items-center">
                        <div className="h-10 w-10 flex-shrink-0">
                          <div className="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                            <span className="text-sm font-medium text-gray-600">
                              {item.product_name.charAt(0)}
                            </span>
                          </div>
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {item.product_name}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="text-sm text-gray-900">{item.sku}</td>
                    <td>
                      <span className={`text-sm font-medium ${
                        item.quantity <= 0 
                          ? 'text-red-600'
                          : item.quantity <= item.min_stock
                          ? 'text-yellow-600'
                          : 'text-green-600'
                      }`}>
                        {item.quantity}
                      </span>
                    </td>
                    <td className="text-sm text-gray-900">{item.min_stock}</td>
                    <td className="text-sm text-gray-900">{item.max_stock || '-'}</td>
                    <td>
                      {item.quantity <= 0 ? (
                        <span className="badge badge-danger flex items-center">
                          <HiExclamationCircle className="h-3 w-3 mr-1" />
                          Out of Stock
                        </span>
                      ) : item.quantity <= item.min_stock ? (
                        <span className="badge badge-warning flex items-center">
                          <HiExclamationCircle className="h-3 w-3 mr-1" />
                          Low Stock
                        </span>
                      ) : (
                        <span className="badge badge-success">In Stock</span>
                      )}
                    </td>
                    <td className="text-sm text-gray-500">
                      {new Date(item.updated_at).toLocaleDateString()}
                    </td>
                    <td>
                      <button
                        onClick={() => {
                          const adjustment = prompt('Enter adjustment (+/- number):')
                          const reason = prompt('Reason for adjustment:')
                          if (adjustment && reason) {
                            handleAdjustment(item, parseInt(adjustment), reason)
                          }
                        }}
                        className="text-primary hover:text-primary-600"
                        title="Adjust stock"
                      >
                        <HiPencil className="h-5 w-5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <div className="text-gray-400 text-6xl mb-4">📦</div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No inventory found</h3>
            <p className="text-gray-500">
              {searchTerm || filterType !== 'all' 
                ? 'Try adjusting your search criteria'
                : 'Add products to start tracking inventory'
              }
            </p>
          </div>
        )}
      </div>
    </div>
  )
}

export default InventoryPage
