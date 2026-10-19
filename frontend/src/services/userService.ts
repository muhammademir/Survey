import api from './api'
import type { User, PaginatedResponse, UserStats, CreateUserForm, UpdateUserForm } from '@/types'

export interface UserListResponse extends PaginatedResponse<User> {
  stats?: UserStats
}

export async function getUsers(params?: {
  page?: number
  role?: string
  approval_status?: string
  search?: string
  per_page?: number
}): Promise<UserListResponse> {
  const { data } = await api.get<UserListResponse>('/admin/users', { params })
  return data
}

export async function getUser(id: number): Promise<User> {
  const { data } = await api.get<{ user: User }>(`/admin/users/${id}`)
  return data.user
}

export async function createUser(form: CreateUserForm): Promise<{ message: string; user: User }> {
  const { data } = await api.post<{ message: string; user: User }>('/admin/users', form)
  return data
}

export async function updateUser(
  id: number,
  form: UpdateUserForm,
): Promise<{ message: string; user: User }> {
  const { data } = await api.put<{ message: string; user: User }>(`/admin/users/${id}`, form)
  return data
}

export async function deleteUser(id: number): Promise<{ message: string }> {
  const { data } = await api.delete<{ message: string }>(`/admin/users/${id}`)
  return data
}

export async function approveUser(id: number): Promise<{ message: string; user: User }> {
  const { data } = await api.patch<{ message: string; user: User }>(`/admin/users/${id}/approve`)
  return data
}

export async function rejectUser(id: number): Promise<{ message: string; user: User }> {
  const { data } = await api.patch<{ message: string; user: User }>(`/admin/users/${id}/reject`)
  return data
}

export async function toggleUserStatus(
  id: number,
): Promise<{ message: string; user: User }> {
  const { data } = await api.patch<{ message: string; user: User }>(`/admin/users/${id}/toggle-status`)
  return data
}
