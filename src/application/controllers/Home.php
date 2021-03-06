<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Usuario_model', 'Usuario_model');
        $this->load->model('Monitoria_model', 'Monitoria_model');
        $this->load->model('Util_model', 'Util');
    }

    public function index()
    {
        //verifica se o usuario esta logado para rediricionar ele para o painel
        if ($this->session->userdata('logged_in')) {
            $this->view_home();
            $DATA['usuarios'] = $this->Usuario_model->getUsuarios();
            return;
        }


        //recupera as monitorias
        $dias_semana = "";
        $arraySemana = ["Sun" => "Domingo", "Mon" => "Segunda-Feira", "Tue" => "Terça-Feira", "Wed" => "Quarta-feira", "Thu" => "Quinta-feira", "Fri" => "Sexta-feira", "Sat" => "Sábado"];
        if (date("D") == "Sat")
            $dias_semana = $arraySemana[date("D")] . "|" . $arraySemana["Sun"];
        else
            $dias_semana = $arraySemana[date("D")] . "|" . $arraySemana[date('D', strtotime('+1 days'))];

        //$dias_semana = "Segunda-feira|Terça-feira";
        $monitorias = $this->Monitoria_model->getMonitoriasListaBydia($dias_semana);

        //para prencher o campo para buscar monitorias é necessario dois arrays em javascript, um com os nomes e outros com id
        $busca_id = "[";
        $busca_nome = "[";
        $id_adicionado = array();
        foreach ($monitorias as $monitoria) {
            if (in_array($monitoria->id_monitoria, $id_adicionado))
                continue;
            $id_adicionado[] = $monitoria->id_monitoria;
            $busca_id .= "'" . addslashes($monitoria->id_monitoria) . "',";
            $busca_nome .= "'" . addslashes(mb_strtoupper($monitoria->nomeDisciplina)) . "',";
        }
        $busca_id .= "]";
        $busca_nome .= "]";

       // $qtd = strlen($monitoria->nomeDisciplina);


        $DATA['busca_id'] = $busca_id;
        $DATA['busca_nome'] = $busca_nome;
        $DATA['monitorias'] = $monitorias;

        $this->load->view('index', $DATA);
    }

    public function view_home()
    {

        //var_dump($DATA);
        $this->load->view('home');
    }

    public function login()
    {
        //verifica se o usuario esta logado para rediricionar ele para o painel
        if ($this->session->userdata('logged_in')) {
            $this->view_home();

            return;
        }

        //recupera os dados do formulario
        $matricula = $this->input->post('matricula');
        $senha = $this->input->post('senha');
        //$password = md5($password); 

        $DATA = $this->Usuario_model->verificaLogin($matricula, $senha);

        //caso encontre um usuario quer dizer que ele esta registrado no sistema
        if ($DATA != NULL) {
            $newdata = array(
                'id_usuario' => $DATA['id_usuario'],
                'nome' => $DATA['nome'],
                'perfil' => $DATA['perfil'],
                'logged_in' => TRUE
            );

            if ($DATA['perfil'] == 'Administrador' or $DATA['perfil'] == 'Professor' or $DATA['perfil'] == 'Monitor') {

                $this->session->set_userdata($newdata);

                $this->view_home($DATA['id_usuario']);
            } else {
                $this->index();
            }

        } //caso o usuario digitou um matricula  e uma senha e nao esteja no BD envia uma msg de erro
        else if (($matricula != NULL || $senha != NULL) && $DATA == NULL) {
            $DADOS['msg'] = 'Matrícula ou Senha inválido';
            $this->load->view('login', $DADOS);
        } //caso contrario, mostra a tela de login
        else {
            $DADOS['msg'] = 'Entre para iniciar sua sessão';
            $this->load->view('login', $DADOS);
        }
    }


    function logout()
    {
        $this->session->unset_userdata('id_usuario');
        $this->session->unset_userdata('nome');
        $this->session->unset_userdata('perfil');
        $this->session->unset_userdata('logged_in');
        session_destroy();
        redirect('Home', 'refresh');
    }

    function alterar_senha_deslogado_view()
    {
        $this->load->view('esqueci_senha');
    }

    function alterar_senha()
    {
        $id_usuario = $this->input->post('id_usuario');

        $senha = $this->input->post('senha');
        $senha2 = $this->input->post('senha2');


        if ($senha == $senha2 && trim($senha) != "") {
            $this->Usuario_model->alterarSenhaDeslogado($id_usuario, $senha);
            redirect('Home', 'refresh');
        } else {
            redirect('Home/recuperaSenha/', 'refresh');
        }
    }

    function recuperaSenha()
    {
        $email = $this->input->post('email');
        $DATA = $this->Usuario_model->recuperaSenha($email);

        if ($this->Usuario_model->recuperaSenha($email)) {
            $this->load->view('trocar_senha',$DATA);
        } else {
            redirect('Home/alterar_senha_deslogado_view/', 'refresh');
        }


    }


}
