import { Outlet } from 'react-router-dom'
import StickyHeader from '../components/layout/StickyHeader'
import Footer from '../components/layout/Footer'

const PublicLayout = () => {
  return (
    <div className="min-h-screen flex flex-col">
      <StickyHeader />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
    </div>
  )
}

export default PublicLayout
