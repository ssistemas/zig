<?php
namespace App\Controllers;
use System\Controller\Controller;
use System\Post\Post;
use System\Get\Get;
use System\Session\Session;
use App\Rules\Logged;

use App\Models\Cliente;
use App\Models\ClienteEndereco;

class ClienteEnderecoController extends Controller
{
	protected $post;
	protected $get;
	protected $layout;
	protected $idEmpresa;
	protected $idUsuario;
	protected $idPerfilUsuarioLogado;

	public function __construct()
	{
		parent::__construct();
		$this->layout = 'default';

		$this->post = new Post();
		$this->get = new Get();
		$this->idEmpresa = Session::get('idEmpresa');
		$this->idUsuario = Session::get('idUsuario');
		$this->idPerfilUsuarioLogado = Session::get('idPerfil');

		$logged = new Logged();
		$logged->isValid();
	}

	public function index($idCliente)
	{
		$cliente = new Cliente();
		$cliente = $cliente->find(out64($idCliente));

		$clienteEndereco = new ClienteEndereco();
		$clienteEnderecos = $clienteEndereco->enderecos($cliente->id);

		$this->view('clienteEndereco/index', $this->layout,
			compact(
				'cliente',
				'clienteEnderecos'
			));
	}

	public function save()
	{
		if ($this->post->hasPost()) {
			$clienteEndereco = new ClienteEndereco();
			$dados = (array) $this->post->data();
			$dados['id_empresa'] = $this->idEmpresa;

			try {
				$clienteEndereco->save($dados);
				return $this->get->redirectTo("clienteEndereco/index", [in64($dados['id_cliente'])]);

			} catch(\Exception $e) {
    		dd($e->getMessage());
    	}
		}
	}

	public function update()
	{
		$clienteEndereco = new ClienteEndereco();
		$dadosClienteEndereco = $clienteEndereco->find($this->post->data()->id);
		$dados = (array) $this->post->only([
		  'cep', 'endereco', 'bairro', 'cidade',
		  'estado', 'numero', 'complemento'
		]);

		try {
			$clienteEndereco->update($dados, $dadosClienteEndereco->id);
			return $this->get->redirectTo("clienteEndereco/index", [in64($dadosClienteEndereco->id_cliente)]);

		} catch(\Exception $e) {
		  dd($e->getMessage());
    }
	}

	public function buscarEnderecoViaCep($cep)
	{
		$cep = out64($cep);
		$cep = str_replace('.', '', $cep);
		$cep = str_replace('-', '', $cep);

		$resposta = fileGet("https://viacep.com.br/ws/{$cep}/json/");

		if ( ! isset($resposta['erro'])) {
			echo json_encode(['status' => true, 'conteudo' => $resposta]);
		} else {
			echo json_encode(['status' => false, 'mensagem' => 'Cep não encontrado!']);
		}
	}

	public function modalFormulario($idCliente, $idEnderecoCliente = false)
	{
		$clienteEndereco = false;

		if ($idEnderecoCliente) {
      $clienteEndereco = new ClienteEndereco();
		  $clienteEndereco = $clienteEndereco->find($idEnderecoCliente);
    }

		$this->view('clienteEndereco/formulario', null,
			compact(
				'clienteEndereco',
				'idCliente'
			));
	}
}
