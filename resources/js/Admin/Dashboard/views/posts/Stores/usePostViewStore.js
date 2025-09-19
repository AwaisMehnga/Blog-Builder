import { create } from "zustand";

export const usePostViewStore = create((set) => ({
    loading:false,
    page: 1,
    per_page: 10,
    search: '',    
    posts: [],
    setLoading: (loading) => set({ loading }),
    setPosts: (posts) => set({ posts }),
    setPage: (page) => set({ page }),
    setPerPage: (per_page) => set({ per_page }),
    setSearch: (search) => set({ search }),
}));