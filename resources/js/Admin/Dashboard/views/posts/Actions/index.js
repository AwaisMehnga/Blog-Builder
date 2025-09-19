import { postViewActions } from "./Post/View";

export const actions = {
    // flatten all actions here
    ...postViewActions,

    // grouped actions
    postView: postViewActions,
};