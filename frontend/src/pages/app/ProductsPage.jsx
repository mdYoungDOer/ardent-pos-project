import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { HiPlus, HiPencil, HiTrash, HiSearch } from 'react-icons/hi'
import api from '../../services/api'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

const ProductsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('')
  const [showAddModal, setShowAddModal] = useState(false)
  const queryClient = useQueryClient()

  const { data: products, isLoading } = useQuery(
    ['products', searchTerm, selectedCategory],
    () => api.get('/products', {
      params: { search: searchTerm, category: selectedCategory }
    }).then(res => res.data)
  )

  const { data: categories } = useQuery(
    'categories',
    () => api.get('/categories').then(res => res.data)
  )

  const deleteProduct = useMutation(
    (id) => api.delete(`/products/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('products')
        toast.success('Product deleted successfully')
      },
      onError: () => {
        toast.error('Failed to delete product')
      }
    }
  )

  const handleDelete = (product) => {
    if (window.confirm(`Are you sure you want to delete "${product.name}"?`)) {
      deleteProduct.mutate(product.id)
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
          <h1 className="text-2xl font-bold text-gray-900">Products</h1>
          <p className="text-gray-600">Manage your product catalog</p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowAddModal(true)}
            className="btn-primary flex items-center"
          >
            <HiPlus className="h-5 w-5 mr-2" />
            Add Product
          </button>
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
                placeholder="Search by name, SKU, or barcode..."
                className="form-input pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
          
          <div>
            <label className="form-label">Category</label>
            <select
              className="form-input"
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
            >
              <option value="">All categories</option>
              {categories?.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Products Grid */}
      <div className="bg-white shadow-sm rounded-lg overflow-hidden">
        {products?.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="table">
              <thead className="table-header">
                <tr>
                  <th>Product</th>
                  <th>SKU</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody className="table-body">
                {products.map((product) => (
                  <tr key={product.id}>
                    <td>
                      <div className="flex items-center">
                        <div className="h-10 w-10 flex-shrink-0">
                          <div className="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                            <span className="text-sm font-medium text-gray-600">
                              {product.name.charAt(0)}
                            </span>
                          </div>
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">
                            {product.name}
                          </div>
                          <div className="text-sm text-gray-500">
                            {product.description}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="text-sm text-gray-900">{product.sku}</td>
                    <td className="text-sm text-gray-900">{product.category_name}</td>
                    <td className="text-sm text-gray-900">
                      ₦{parseFloat(product.price).toLocaleString()}
                    </td>
                    <td>
                      <span className={`badge ${
                        product.quantity <= product.min_stock 
                          ? 'badge-danger' 
                          : product.quantity <= product.min_stock * 2
                          ? 'badge-warning'
                          : 'badge-success'
                      }`}>
                        {product.quantity}
                      </span>
                    </td>
                    <td>
                      <span className={`badge ${
                        product.status === 'active' ? 'badge-success' : 'badge-danger'
                      }`}>
                        {product.status}
                      </span>
                    </td>
                    <td>
                      <div className="flex items-center space-x-2">
                        <button
                          className="text-primary hover:text-primary-600"
                          title="Edit product"
                        >
                          <HiPencil className="h-5 w-5" />
                        </button>
                        <button
                          onClick={() => handleDelete(product)}
                          className="text-red-600 hover:text-red-700"
                          title="Delete product"
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
            <div className="text-gray-400 text-6xl mb-4">📦</div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No products found</h3>
            <p className="text-gray-500 mb-6">
              {searchTerm || selectedCategory 
                ? 'Try adjusting your search criteria'
                : 'Get started by adding your first product'
              }
            </p>
            <button
              onClick={() => setShowAddModal(true)}
              className="btn-primary"
            >
              Add Product
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

export default ProductsPage
