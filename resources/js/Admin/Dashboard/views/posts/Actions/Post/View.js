import axios from "axios";
import { usePostViewStore } from "../../Stores/usePostViewStore";
import { actions } from "..";
import toast from "react-hot-toast";

const postViewStore = usePostViewStore.getState();

export const postViewActions = {
    getPost: async (page=1, per_page=10, search='') => {
        try{
            postViewStore.setLoading(true);
            const response = await axios.get('/api/blogs', {
                params: {
                    page,
                    per_page,
                    search
                }
            });
            if(response?.data?.success) postViewStore.setPosts(response.data);
        }catch(error){
            console.error("Error fetching posts:", error);
        }finally{
            postViewStore.setLoading(false);
        }
    },
    bulk: async (ids, status='publish') => {
        try{
            if(!window.confirm(`Are you sure you want to ${status} all posts?`)) return;
            postViewStore.setLoading(true);
            const payload = {
                ids: ids==='all' ? 'all' : ids.join(','),
                action: status
            }
            const response = await axios.post('/api/blogs/bulk', payload);
            if(!response?.data?.success) toast.error(response?.data?.message || `Failed to ${status} posts`);
            toast.success(response?.data?.message || `Posts ${status} successfully`);
        }catch(error){
            console.error(`Error ${status} posts:`, error);
            toast.error(`An error occurred while ${status} posts`);
        }finally{
            postViewStore.setLoading(false);
            actions.postView.getPost(postViewStore.page, postViewStore.per_page, postViewStore.search);
        }
    },
}