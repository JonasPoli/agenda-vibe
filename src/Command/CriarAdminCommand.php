<?php

namespace App\Command;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:admin:criar',
    description: 'Cria um administrador do painel (ou redefine a senha de um existente)',
    help: <<<'AJUDA'
        Cria um usuário com acesso à área administrativa (/admin).
        A senha é sempre pedida de forma oculta, para não ficar no histórico do terminal.

          <info>php bin/console app:admin:criar</info>
          <info>php bin/console app:admin:criar maria@exemplo.com "Maria Souza"</info>

        Para trocar a senha de um administrador que já existe:

          <info>php bin/console app:admin:criar maria@exemplo.com --redefinir-senha</info>
        AJUDA,
)]
class CriarAdminCommand extends Command
{
    private const SENHA_MINIMA = 8;

    public function __construct(
        private UsuarioRepository $usuarios,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'E-mail usado no login')
            ->addArgument('nome', InputArgument::OPTIONAL, 'Nome do administrador')
            ->addOption('redefinir-senha', null, InputOption::VALUE_NONE, 'Troca a senha de um usuário existente (e garante que ele seja administrador)');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getArgument('email')) {
            $input->setArgument('email', $io->ask('E-mail', null, $this->validarEmail(...)));
        }

        $existe = $this->usuarios->porEmail((string) $input->getArgument('email'));
        if (!$existe && !$input->getArgument('nome')) {
            $input->setArgument('nome', $io->ask('Nome', null, function (?string $nome): string {
                if (!trim((string) $nome)) {
                    throw new \RuntimeException('Informe o nome.');
                }

                return trim($nome);
            }));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->isInteractive()) {
            $io->error('Este comando pede a senha de forma interativa. Rode sem a opção --no-interaction.');

            return Command::FAILURE;
        }

        $email = $this->validarEmail((string) $input->getArgument('email'));
        $usuario = $this->usuarios->porEmail($email);
        $redefinir = (bool) $input->getOption('redefinir-senha');

        if ($usuario && !$redefinir) {
            $io->error(sprintf('Já existe um usuário com o e-mail %s. Para trocar a senha, use --redefinir-senha.', $email));

            return Command::FAILURE;
        }

        if (!$usuario && $redefinir) {
            $io->error(sprintf('Não existe usuário com o e-mail %s.', $email));

            return Command::FAILURE;
        }

        $usuario ??= (new Usuario())->setEmail($email)->setNome((string) $input->getArgument('nome'));
        $usuario->tornarAdmin();

        $erros = $this->validator->validate($usuario);
        if (\count($erros) > 0) {
            foreach ($erros as $erro) {
                $io->error($erro->getMessage());
            }

            return Command::FAILURE;
        }

        $senha = $this->perguntarSenha($io);
        $usuario->setPassword($this->hasher->hashPassword($usuario, $senha));

        $this->em->persist($usuario);
        $this->em->flush();

        $io->success($redefinir
            ? sprintf('Senha de %s <%s> redefinida.', $usuario->getNome(), $usuario->getEmail())
            : sprintf('Administrador %s <%s> criado. Já pode entrar em /admin.', $usuario->getNome(), $usuario->getEmail()));

        return Command::SUCCESS;
    }

    private function perguntarSenha(SymfonyStyle $io): string
    {
        $pergunta = (new Question(sprintf('Senha (mínimo de %d caracteres)', self::SENHA_MINIMA)))
            ->setHidden(true)
            ->setHiddenFallback(false)
            ->setMaxAttempts(3)
            ->setValidator(function (?string $senha): string {
                if (mb_strlen((string) $senha) < self::SENHA_MINIMA) {
                    throw new \RuntimeException(sprintf('A senha precisa ter pelo menos %d caracteres.', self::SENHA_MINIMA));
                }

                return $senha;
            });

        $senha = $io->askQuestion($pergunta);
        $confirmacao = $io->askQuestion((new Question('Repita a senha'))->setHidden(true)->setHiddenFallback(false));

        if ($senha !== $confirmacao) {
            throw new \RuntimeException('As senhas não conferem.');
        }

        return $senha;
    }

    private function validarEmail(?string $email): string
    {
        $email = mb_strtolower(trim((string) $email));
        $erros = $this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);

        if (\count($erros) > 0) {
            throw new \RuntimeException(sprintf('E-mail inválido: "%s".', $email));
        }

        return $email;
    }
}
