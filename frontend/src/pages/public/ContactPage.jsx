import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { HiMail, HiPhone, HiLocationMarker } from 'react-icons/hi'
import toast from 'react-hot-toast'
import StickyHeader from '../../components/layout/StickyHeader'
import Preloader from '../../components/ui/Preloader'

const ContactPage = () => {
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false)

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm()

  useEffect(() => {
    // Simulate loading time for consistency
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);
    return () => clearTimeout(timer);
  }, []);

  if (loading) {
    return <Preloader />;
  }

  const onSubmit = async (data) => {
    setIsSubmitting(true)
    try {
      // Submit to API endpoint
      const response = await fetch('/api/contact-submissions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          first_name: data.first_name,
          last_name: data.last_name,
          email: data.email,
          company: data.company || null,
          subject: data.subject,
          message: data.message
        })
      });

      const result = await response.json();

      if (result.success) {
        toast.success('Message sent successfully! We\'ll get back to you soon.')
        reset()
      } else {
        throw new Error(result.error || 'Failed to send message')
      }
    } catch (error) {
      console.error('Contact form error:', error);
      toast.error('Failed to send message. Please try again.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="bg-white">
      <StickyHeader />
      {/* Hero Section */}
      <div className="relative bg-primary-50 py-16 mt-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
              Get in Touch
            </h1>
            <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
              Have questions about Ardent POS? We're here to help you succeed.
            </p>
          </div>
        </div>
      </div>

      {/* Contact Section */}
      <div className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {/* Contact Information */}
            <div>
              <h2 className="text-2xl font-extrabold text-gray-900 sm:text-3xl">
                Let's talk about your business
              </h2>
              <p className="mt-3 text-lg text-gray-500">
                Whether you're just getting started or looking to scale, our team is here to help 
                you make the most of Ardent POS.
              </p>

              <div className="mt-9 space-y-6">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <HiMail className="h-6 w-6 text-primary" />
                  </div>
                  <div className="ml-3 text-base text-gray-500">
                    <p>Email us at</p>
                    <p className="font-medium text-gray-900">support@ardentpos.com</p>
                  </div>
                </div>
                
                <div className="flex">
                  <div className="flex-shrink-0">
                    <HiPhone className="h-6 w-6 text-primary" />
                  </div>
                  <div className="ml-3 text-base text-gray-500">
                    <p>Call us at</p>
                    <p className="font-medium text-gray-900">+233 (0) 302 527 484</p>
                  </div>
                </div>
                
                <div className="flex">
                  <div className="flex-shrink-0">
                    <HiLocationMarker className="h-6 w-6 text-primary" />
                  </div>
                  <div className="ml-3 text-base text-gray-500">
                    <p>Visit us at</p>
                    <p className="font-medium text-gray-900">
                      Mother Love St., Adenta<br />
                      Accra, Ghana
                    </p>
                  </div>
                </div>
              </div>

              <div className="mt-8 p-6 bg-gray-50 rounded-lg">
                <h3 className="text-lg font-medium text-gray-900">Business Hours</h3>
                <div className="mt-3 space-y-1 text-sm text-gray-500">
                  <p>Monday - Friday: 9:00 AM - 6:00 PM GMT</p>
                  <p>Saturday: 10:00 AM - 4:00 PM GMT</p>
                  <p>Sunday: Closed</p>
                </div>
              </div>
            </div>

            {/* Contact Form */}
            <div className="bg-white shadow-sm border border-gray-200 rounded-lg p-6">
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label htmlFor="first_name" className="form-label">
                      First name
                    </label>
                    <input
                      type="text"
                      id="first_name"
                      className="form-input"
                      {...register('first_name', {
                        required: 'First name is required'
                      })}
                    />
                    {errors.first_name && (
                      <p className="form-error">{errors.first_name.message}</p>
                    )}
                  </div>

                  <div>
                    <label htmlFor="last_name" className="form-label">
                      Last name
                    </label>
                    <input
                      type="text"
                      id="last_name"
                      className="form-input"
                      {...register('last_name', {
                        required: 'Last name is required'
                      })}
                    />
                    {errors.last_name && (
                      <p className="form-error">{errors.last_name.message}</p>
                    )}
                  </div>
                </div>

                <div>
                  <label htmlFor="email" className="form-label">
                    Email
                  </label>
                  <input
                    type="email"
                    id="email"
                    className="form-input"
                    {...register('email', {
                      required: 'Email is required',
                      pattern: {
                        value: /^\S+@\S+$/i,
                        message: 'Invalid email address'
                      }
                    })}
                  />
                  {errors.email && (
                    <p className="form-error">{errors.email.message}</p>
                  )}
                </div>

                <div>
                  <label htmlFor="company" className="form-label">
                    Company (optional)
                  </label>
                  <input
                    type="text"
                    id="company"
                    className="form-input"
                    {...register('company')}
                  />
                </div>

                <div>
                  <label htmlFor="subject" className="form-label">
                    Subject
                  </label>
                  <select
                    id="subject"
                    className="form-input"
                    {...register('subject', {
                      required: 'Please select a subject'
                    })}
                  >
                    <option value="">Select a subject</option>
                    <option value="sales">Sales Inquiry</option>
                    <option value="support">Technical Support</option>
                    <option value="billing">Billing Question</option>
                    <option value="partnership">Partnership</option>
                    <option value="other">Other</option>
                  </select>
                  {errors.subject && (
                    <p className="form-error">{errors.subject.message}</p>
                  )}
                </div>

                <div>
                  <label htmlFor="message" className="form-label">
                    Message
                  </label>
                  <textarea
                    id="message"
                    rows={4}
                    className="form-input"
                    placeholder="Tell us how we can help you..."
                    {...register('message', {
                      required: 'Message is required',
                      minLength: {
                        value: 10,
                        message: 'Message must be at least 10 characters'
                      }
                    })}
                  />
                  {errors.message && (
                    <p className="form-error">{errors.message.message}</p>
                  )}
                </div>

                <div>
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="btn-primary w-full flex justify-center"
                  >
                    {isSubmitting ? 'Sending...' : 'Send Message'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      {/* FAQ Section */}
      <div className="bg-gray-50 py-16">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Quick Answers
            </h2>
            <p className="mt-4 text-lg text-gray-500">
              Looking for immediate help? Check out these common questions.
            </p>
          </div>

          <div className="mt-12 space-y-8">
            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                How quickly can I get started?
              </h3>
              <p className="text-gray-600">
                You can sign up and start using Ardent POS immediately. Setup takes less than 5 minutes.
              </p>
            </div>

            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                Do you offer training?
              </h3>
              <p className="text-gray-600">
                Yes! We provide free onboarding sessions and comprehensive documentation to get your team up to speed.
              </p>
            </div>

            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                What if I need help during setup?
              </h3>
              <p className="text-gray-600">
                Our support team is available via email, phone, and live chat to help you every step of the way.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ContactPage
