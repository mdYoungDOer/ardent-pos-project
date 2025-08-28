import { Outlet } from 'react-router-dom'
import StickyHeader from '../components/layout/StickyHeader'
import Footer from '../components/layout/Footer'
import ChatWidget from '../components/support/ChatWidget'

const PublicLayout = () => {
  return (
    <div className="min-h-screen flex flex-col">
      <StickyHeader />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
      <ChatWidget />
    </div>
  )
}

export default PublicLayout
