function index(req, res){
    res.render('tasks/index');
};

function create(req, res) {
    res.render('tasks/create');
};

function store(req, res){
    console.log(req.body)
};

module.exports = {
    index: index,
    create: create,
    store:store
}