import { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { apiService } from '@/services/api';

interface User {
  id: number;
  email: string;
  full_name: string;
  role: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (email: string, password: string, fullName: string) => Promise<void>;
  signOut: () => Promise<void>;
  updateProfile: (data: { full_name?: string; email?: string }) => Promise<void>;
  changePassword: (oldPassword: string, newPassword: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      if (apiService.isAuthenticated()) {
        const response = await apiService.getMe();
        if (response.success && response.data?.user) {
          setUser(response.data.user);
        } else {
          // Session invalid, clear it
          apiService.clearSession();
        }
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      apiService.clearSession();
    } finally {
      setLoading(false);
    }
  };

  const signIn = async (email: string, password: string) => {
    try {
      const response = await apiService.login(email, password);
      if (response.success && response.data?.user) {
        setUser(response.data.user);
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error) {
      console.error('Sign in failed:', error);
      throw error;
    }
  };

  const signUp = async (email: string, password: string, fullName: string) => {
    try {
      const response = await apiService.register(email, password, fullName);
      if (!response.success) {
        throw new Error(response.message || 'Registration failed');
      }
      // After successful registration, automatically sign in
      await signIn(email, password);
    } catch (error) {
      console.error('Sign up failed:', error);
      throw error;
    }
  };

  const signOut = async () => {
    try {
      await apiService.logout();
    } catch (error) {
      console.error('Sign out failed:', error);
    } finally {
      setUser(null);
      apiService.clearSession();
    }
  };

  const updateProfile = async (data: { full_name?: string; email?: string }) => {
    try {
      const response = await apiService.updateProfile(data);
      if (response.success) {
        // Refresh user data
        await checkAuth();
      } else {
        throw new Error(response.message || 'Profile update failed');
      }
    } catch (error) {
      console.error('Profile update failed:', error);
      throw error;
    }
  };

  const changePassword = async (oldPassword: string, newPassword: string) => {
    try {
      const response = await apiService.changePassword(oldPassword, newPassword);
      if (!response.success) {
        throw new Error(response.message || 'Password change failed');
      }
    } catch (error) {
      console.error('Password change failed:', error);
      throw error;
    }
  };

  const value = {
    user,
    loading,
    signIn,
    signUp,
    signOut,
    updateProfile,
    changePassword,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuthPHP() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuthPHP must be used within an AuthProvider');
  }
  return context;
}

export default useAuthPHP;
